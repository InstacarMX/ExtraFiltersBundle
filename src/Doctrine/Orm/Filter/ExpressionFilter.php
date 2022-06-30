<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Instacar\ExtraFiltersBundle\Doctrine\Common\Filter\ExpressionLanguage;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class ExpressionFilter extends AbstractContextAwareFilter
{
    private ExpressionLanguage $expressionLanguage;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ExpressionLanguage $expressionLanguage
     * @param RequestStack|null $requestStack
     * @param LoggerInterface|null $logger
     * @param array<string, string>|null $properties
     * @param ContextAwareFilterInterface[] $filters
     * @param NameConverterInterface|null $nameConverter
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ExpressionLanguage $expressionLanguage,
        ?RequestStack $requestStack = null,
        LoggerInterface $logger = null,
        array $properties = null,
        array $filters = null,
        NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        $this->expressionLanguage = $expressionLanguage;
        $this->expressionLanguage->registerProvider(new FilterExpressionProvider($filters));
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

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
        /*array $context = []*/
    ): void {
        $context = func_get_arg(6);
        if (($expression = $this->properties[$property] ?? null) === null) {
            return;
        }

        try {
            $this->expressionLanguage->lint(
                $expression,
                ['property', 'value', 'queryBuilder', 'queryNameGenerator', 'resourceClass', 'operationName', 'context'],
            );
        } catch (SyntaxError $e) {
            $this->logger->notice('Invalid filter ignored', ['exception' => $e]);

            return;
        }

        $queryExpression = $this->expressionLanguage->evaluate($expression, [
            'property' => $property,
            'value' => $value,
            'queryBuilder' => $queryBuilder,
            'queryNameGenerator' => $queryNameGenerator,
            'resourceClass' => $resourceClass,
            'operationName' => $operationName,
            'context' => $context,
        ]);
        $queryBuilder->andWhere($queryExpression);
    }
}
