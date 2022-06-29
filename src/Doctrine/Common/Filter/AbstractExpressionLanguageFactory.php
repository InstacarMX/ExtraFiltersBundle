<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Common\Filter;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

abstract class AbstractExpressionLanguageFactory
{
    /**
     * @var ExpressionFunctionProviderInterface[]
     */
    private array $baseProviders;

    private ContainerInterface $filterLocator;

    private ResourceMetadataFactoryInterface $resourceMetadataFactory;

    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers
     * @param ContainerInterface $filterLocator
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     */
    public function __construct(
        iterable $providers,
        ContainerInterface $filterLocator,
        ResourceMetadataFactoryInterface $resourceMetadataFactory
    ) {
        $this->filterLocator = $filterLocator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->baseProviders = $providers instanceof \Traversable ? iterator_to_array($providers) : (array) $providers;
    }

    public function provide(string $resourceClass, string $operationName): ExpressionLanguage
    {
        $filters = $this->getFilters($resourceClass, $operationName);
        $filterProviders = array_map([$this, 'createFilterProvider'], $filters);

        return new ExpressionLanguage([...$this->baseProviders, ...$filterProviders]);
    }

    /**
     * @param string $resourceClass
     * @param string $operationName
     * @return FilterInterface[]
     */
    protected function getFilters(string $resourceClass, string $operationName): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        $filters = [];
        foreach ($resourceFilters as $filterId) {
            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;

            if ($filter === $this) {
                break;
            }
            if ($this->supports($filter)) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    abstract protected function supports(FilterInterface $filter): bool;

    abstract protected function createFilterProvider(FilterInterface $filter): ExpressionFunctionProviderInterface;
}
