<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Generator;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Instacar\ExtraFiltersBundle\Doctrine\Common\Generator\MatchGeneratorTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class MatchGenerator extends AbstractDoctrineOrmGenerator implements SearchFilterInterface
{
    use MatchGeneratorTrait;

    public const DOCTRINE_INTEGER_TYPE = Types::INTEGER;

    protected static string $name = 'match';

    private IdentifiersExtractorInterface $identifiersExtractor;

    public function __construct(
        ManagerRegistry $managerRegistry,
        IriConverterInterface $iriConverter,
        IdentifiersExtractorInterface $identifiersExtractor,
        PropertyAccessorInterface $propertyAccessor = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($managerRegistry, $logger);

        $this->iriConverter = $iriConverter;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    public function process(
        string $property,
        ?string $strategy,
        array $parameters,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?string $operationName
    ) {
        if (
            null === $value ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return null;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return null;
        }

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty(
                $property,
                $alias,
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass
            );
        }

        $caseSensitive = true;
        $strategy = $strategy ?? self::STRATEGY_EXACT;

        // prefixing the strategy with i makes it case insensitive
        if (str_starts_with($strategy, 'i')) {
            $strategy = substr($strategy, 1);
            $caseSensitive = false;
        }

        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            if ('id' === $field) {
                $values = array_map([$this, 'getIdFromValue'], $values);
            }

            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf(
                        'Values for field "%s" are not valid according to the doctrine type.',
                        $field,
                    )),
                ]);

                return null;
            }

            return $this->addWhereByStrategy($strategy, $queryBuilder, $queryNameGenerator, $alias, $field, $values, $caseSensitive);
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return null;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);

        $associationResourceClass = $metadata->getAssociationTargetClass($field);
        $associationFieldIdentifier = $this->identifiersExtractor->getIdentifiersFromResourceClass($associationResourceClass)[0];
        $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf(
                    'Values for field "%s" are not valid according to the doctrine type.',
                    $field,
                )),
            ]);

            return null;
        }

        $associationAlias = $alias;
        $associationField = $field;
        if ($metadata->isCollectionValuedAssociation($associationField) || $metadata->isAssociationInverseSide($field)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $associationField);
            $associationField = $associationFieldIdentifier;
        }

        return $this->addWhereByStrategy(
            $strategy,
            $queryBuilder,
            $queryNameGenerator,
            $associationAlias,
            $associationField,
            $values,
            $caseSensitive,
        );
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @param string $strategy
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $alias
     * @param string $field
     * @param mixed $values
     * @param bool $caseSensitive
     * @return Expr\Orx|Expr\Func|Expr\Comparison
     * @throws InvalidArgumentException If strategy does not exist
     */
    protected function addWhereByStrategy(
        string $strategy,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        string $field,
        $values,
        bool $caseSensitive
    ) {
        if (!is_array($values)) {
            $values = [$values];
        }

        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = ':' . $queryNameGenerator->generateParameterName($field);
        $aliasedField = sprintf('%s.%s', $alias, $field);

        if (!$strategy || self::STRATEGY_EXACT === $strategy) {
            if (1 === count($values)) {
                $queryBuilder->setParameter($valueParameter, $values[0]);

                return $queryBuilder->expr()->eq($wrapCase($aliasedField), $wrapCase($valueParameter));
            }

            $queryBuilder->setParameter($valueParameter, $caseSensitive ? $values : array_map('strtolower', $values));

            return $queryBuilder->expr()->in($wrapCase($aliasedField), $valueParameter);
        }

        $ors = [];
        $parameters = [];
        foreach ($values as $key => $value) {
            $keyValueParameter = sprintf('%s_%s', $valueParameter, $key);
            $parameters[$caseSensitive ? $value : strtolower($value)] = $keyValueParameter;

            switch ($strategy) {
                case self::STRATEGY_PARTIAL:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string)$queryBuilder->expr()->concat("'%'", $keyValueParameter, "'%'"))
                    );
                    break;

                case self::STRATEGY_START:
                    $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string)$queryBuilder->expr()->concat($keyValueParameter, "'%'"))
                    );
                    break;

                case self::STRATEGY_END:
                    $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string)$queryBuilder->expr()->concat("'%'", $keyValueParameter))
                    );
                    break;

                case self::STRATEGY_WORD_START:
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like(
                            $wrapCase($aliasedField),
                            $wrapCase((string)$queryBuilder->expr()->concat($keyValueParameter, "'%'"))
                        ),
                        $queryBuilder->expr()->like(
                            $wrapCase($aliasedField),
                            $wrapCase((string)$queryBuilder->expr()->concat("'% '", $keyValueParameter, "'%'"))
                        ),
                    );
                    break;

                default:
                    throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
            }
        }

        array_walk($parameters, [$queryBuilder, 'setParameter']);
        return $queryBuilder->expr()->orX(...$ors);
    }

    /**
     * Creates a function that will wrap a Doctrine expression according to the
     * specified case sensitivity.
     *
     * For example, "o.name" will get wrapped into "LOWER(o.name)" when $caseSensitive
     * is false.
     */
    protected function createWrapCase(bool $caseSensitive): \Closure
    {
        return static function (string $expr) use ($caseSensitive): string {
            if ($caseSensitive) {
                return $expr;
            }

            return sprintf('LOWER(%s)', $expr);
        };
    }

    protected function getIriConverter(): IriConverterInterface
    {
        return $this->iriConverter;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
    }
}
