<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Instacar\ExtraFiltersBundle\Util\StringUtil;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FilterExpressionFunctionProvider implements DoctrineOrmExpressionFunctionProviderInterface
{
    /**
     * @var FilterInterface[]
     */
    protected array $filters;

    protected IriConverterInterface $iriConverter;

    protected PropertyAccessorInterface $propertyAccessor;

    /**
     * @param FilterInterface[] $filters
     */
    public function __construct(array $filters, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->filters = $filters;
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    public function getFunctions(): array
    {
        return array_map(function ($filter) {
            return new ExpressionFunction(
                $this->normalizeFilterName(get_class($filter)),
                static function () {
                    // Uncompilable
                },
                function ($arguments, string $property = null, string $strategy = null, $value = null) use ($filter) {
                    return $this->applyFilter(
                        $filter,
                        $property ?? $arguments['property'],
                        $strategy,
                        $value ?? $arguments['value'],
                        $arguments['queryBuilder'],
                        $arguments['queryNameGenerator'],
                        $arguments['resourceClass'],
                        $arguments['operation'],
                        $arguments['context'],
                    );
                },
            );
        }, $this->filters);
    }

    /**
     * @param FilterInterface $filter
     * @param string $property
     * @param string|null $strategy
     * @param mixed $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param mixed[] $context
     * @return mixed
     */
    protected function applyFilter(
        FilterInterface $filter,
        string $property,
        ?string $strategy,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation,
        array $context,
    ): mixed {
        // Save and reset the where clause to obtain a untainted expression from the filter
        $originalWhere = $queryBuilder->getDQLPart('where');
        $queryBuilder->resetDQLPart('where');

        try {
            // Trespass the property protection for $properties with reflection
            $reflectionClass = new \ReflectionClass($filter);
            $reflectedProperty = $reflectionClass->getProperty('properties');

            $reflectedProperty->setValue($filter, [$property => $strategy]);
        } catch (\ReflectionException) {
            // The property $properties does not exist, ignored it (maybe a custom filter)
        }

        // Bootstrap the context
        $context['filters'] = [$property => $this->normalizeValue($value)];
        $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

        // Save the generated expression from the filter and restore the where clause
        $expression = $queryBuilder->getDQLPart('where');
        $queryBuilder->where($originalWhere);

        return $expression;
    }

    /**
     * Function to cast all the values to string, in the case that the expression operates with it.
     * @param mixed $value
     * @return string|mixed[]
     */
    protected function normalizeValue(mixed $value): string|array
    {
        if (is_object($value)) {
            return $this->getIriFromResource($value);
        }
        if (is_array($value)) {
            $normalizedArray = [];
            foreach ($value as $key => $item) {
                $normalizedArray[$key] = self::normalizeValue($item);
            }

            return $normalizedArray;
        }

        return (string) $value;
    }

    protected function normalizeFilterName(string $className): string
    {
        $classNamespace = explode('\\', $className);
        $shortName = $classNamespace[count($classNamespace) - 1];
        $shortName = StringUtil::removeSuffix($shortName, 'Filter');

        return lcfirst($shortName);
    }

    protected function getIriFromResource(object $value): string
    {
        // Extracted from SearchFilterTrait of API Platform
        try {
            return $this->iriConverter->getIriFromResource($value);
        } catch (InvalidArgumentException) {
            // Do nothing, try to cast to string
        }

        return (string) $value;
    }
}
