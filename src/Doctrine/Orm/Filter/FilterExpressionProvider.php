<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression\DoctrineOrmExpressionProviderInterface;
use Instacar\ExtraFiltersBundle\Util\StringUtil;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class FilterExpressionProvider implements DoctrineOrmExpressionProviderInterface
{
    /**
     * @var ContextAwareFilterInterface[]
     */
    protected array $filters;

    private bool $innerJoinsLeft;

    /**
     * @param ContextAwareFilterInterface[] $filters
     */
    public function __construct(array $filters, bool $innerJoinsLeft = false)
    {
        $this->filters = $filters;
        $this->innerJoinsLeft = $innerJoinsLeft;
    }

    public function getFunctions(): array
    {
        $innerJoinsLeft = $this->innerJoinsLeft;
        return array_map(static function ($filter) use ($innerJoinsLeft) {
            return new ExpressionFunction(
                self::normalizeFilterName(get_class($filter)),
                static function () {
                    // Uncompilable
                },
                static function (
                    $arguments,
                    string $property = null,
                    string $strategy = null,
                    $value = null
                ) use (
                    $filter,
                    $innerJoinsLeft
                ) {
                    return self::applyFilter(
                        $filter,
                        $property ?? $arguments['property'],
                        $strategy,
                        $value ?? $arguments['value'],
                        $arguments['queryBuilder'],
                        $arguments['queryNameGenerator'],
                        $arguments['resourceClass'],
                        $arguments['operationName'],
                        $arguments['context'],
                        $innerJoinsLeft,
                    );
                },
            );
        }, $this->filters);
    }

    /**
     * @param ContextAwareFilterInterface $filter
     * @param string $property
     * @param string|null $strategy
     * @param mixed $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param string|null $operationName
     * @param mixed[] $context
     * @param bool $innerJoinsLeft
     * @return mixed
     */
    protected static function applyFilter(
        ContextAwareFilterInterface $filter,
        string $property,
        ?string $strategy,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?string $operationName,
        array $context,
        bool $innerJoinsLeft
    ) {
        // Save and reset the where clause to obtain a untainted expression from the filter
        $originalWhere = $queryBuilder->getDQLPart('where');
        $queryBuilder->resetDQLPart('where');

        try {
            // Trespass the property protection for $properties with reflection for PHP < 8.1
            $reflectionClass = new \ReflectionClass($filter);
            $reflectedProperty = $reflectionClass->getProperty('properties');

            $reflectedProperty->setAccessible(true);
            $reflectedProperty->setValue($filter, [$property => $strategy]);
            $reflectedProperty->setAccessible(false);
        } catch (\ReflectionException $e) {
            // The property $properties does not exist, ignored it (maybe a custom filter)
        }

        // Bootstrap the context
        $context['filters'] = [$property => self::normalizeValue($value)];
        $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

        // Save the generated expression from the filter and restore the where clause
        $expression = $queryBuilder->getDQLPart('where');
        $queryBuilder->where($originalWhere);

        if ($innerJoinsLeft) {
            $joinPart = $queryBuilder->getDQLPart('join');
            $result = [];
            foreach ($joinPart as $rootAlias => $joins) {
                /** @var Join $joinExp */
                foreach ($joins as $i => $joinExp) {
                    if ($joinExp->getJoinType() === Join::INNER_JOIN) {
                        $result[$rootAlias][$i] = new Join(
                            Join::LEFT_JOIN,
                            $joinExp->getJoin(),
                            $joinExp->getAlias(),
                            $joinExp->getConditionType(),
                            $joinExp->getCondition(),
                            $joinExp->getIndexBy()
                        );
                    } else {
                        $result[$rootAlias][$i] = $joinExp;
                    }
                }
            }
            $queryBuilder->add('join', $result);
        }

        return $expression;
    }

    /**
     * Function to cast all the values to string, in the case that the expression operates with it.
     * @param mixed $value
     * @return string|mixed[]
     */
    protected static function normalizeValue($value)
    {
        if (!is_array($value)) {
            return (string) $value;
        }

        $normalizedArray = [];
        foreach ($value as $key => $item) {
            $normalizedArray[$key] = self::normalizeValue($item);
        }

        return $normalizedArray;
    }

    protected static function normalizeFilterName(string $className): string
    {
        $classNamespace = explode('\\', $className);
        $shortName = $classNamespace[count($classNamespace) - 1];
        $shortName = StringUtil::removeSuffix($shortName, 'Filter');

        return lcfirst($shortName);
    }
}
