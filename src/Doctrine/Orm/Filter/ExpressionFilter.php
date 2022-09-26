<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class ExpressionFilter extends AbstractFilter
{
    private ExpressionLanguage $expressionLanguage;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ExpressionLanguage $expressionLanguage
     * @param LoggerInterface|null $logger
     * @param array<string, string>|null $properties
     * @param NameConverterInterface|null $nameConverter
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ExpressionLanguage $expressionLanguage,
        LoggerInterface $logger = null,
        array $properties = null,
        NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param string $resourceClass
     * @return mixed[]
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->properties as $property => $unused) {
            $description[$property] = [
                'property' => null,
                'required' => false,
                'type' => 'string',
            ];
        }

        return $description;
    }

    /**
     * @param string $property
     * @param mixed $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param mixed[] $context
     * @return void
     */
    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = [],
    ): void {
        if (($expression = $this->properties[$property] ?? null) === null) {
            return;
        }

        try {
            $this->expressionLanguage->lint(
                $expression,
                ['property', 'value', 'queryBuilder', 'queryNameGenerator', 'resourceClass', 'operation', 'context'],
            );

            $queryExpression = $this->expressionLanguage->evaluate($expression, [
                'property' => $property,
                'value' => $value,
                'queryBuilder' => $queryBuilder,
                'queryNameGenerator' => $queryNameGenerator,
                'resourceClass' => $resourceClass,
                'operation' => $operation,
                'context' => $context,
            ]);
            $queryBuilder->andWhere($queryExpression);
        } catch (\Exception $e) {
            $this->logger->notice('Invalid filter ignored', ['exception' => $e]);
        }
    }
}
