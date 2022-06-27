<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Instacar\ExtraFiltersBundle\Doctrine\Common\Filter\ExpressionLanguage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ExpressionFilter extends AbstractContextAwareFilter
{
    private ExpressionLanguage $expressionLanguage;

    /** @var FilterCollection|ContainerInterface */
    private $filterLocator;

    private ResourceMetadataFactoryInterface $resourceMetadataFactory;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ExpressionLanguage $expressionLanguage
     * @param ContainerInterface|FilterCollection $filterLocator
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param RequestStack|null $requestStack
     * @param LoggerInterface|null $logger
     * @param array<string, string>|null $properties
     * @param NameConverterInterface|null $nameConverter
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ExpressionLanguage $expressionLanguage,
        $filterLocator,
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        ?RequestStack $requestStack = null,
        LoggerInterface $logger = null,
        array $properties = null,
        NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        $this->expressionLanguage = $expressionLanguage;
        $this->filterLocator = $filterLocator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param string|null $operationName
     * @param mixed[] $context
     */
    public function apply(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null,
        array $context = []
    ): void {
        $filters = $this->getFilters($resourceClass, $operationName);
        $this->expressionLanguage->registerProvider(new FilterExpressionProvider($filters));

        parent::apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
    }


    /**
     * @param string $resourceClass
     * @return mixed[]
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->properties as $property) {
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

    /**
     * @param string $resourceClass
     * @param string $operationName
     * @return ContextAwareFilterInterface[]
     */
    private function getFilters(string $resourceClass, string $operationName): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        $filters = [];
        foreach ($resourceFilters as $filterId) {
            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;

            if ($filter === $this) {
                break;
            }
            if ($filter instanceof ContextAwareFilterInterface) {
                $filters[$filterId] = $filter;
            }
        }

        return $filters;
    }
}
