<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\DoctrineOrmExpressionProviderInterface;
use Instacar\ExtraFiltersBundle\Util\StringUtil;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class FilterExpressionProvider implements DoctrineOrmExpressionProviderInterface
{
    /**
     * @var ContextAwareFilterInterface[]
     */
    private array $filters;

    /**
     * @param ContextAwareFilterInterface[] $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function getFunctions(): array
    {
        return array_map(static function ($filter) {
            return new ExpressionFunction(
                self::normalizeFilterName(get_class($filter)),
                static function () {
                    // Uncompilable
                },
                static function ($arguments, string $property = null, string $strategy = null, $value = null) use ($filter) {
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
                    );
                },
            );
        }, $this->filters);
    }

    /**
     * @param ContextAwareFilterInterface $filter
     * @param string $property
     * @param mixed $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param string|null $operationName
     * @param mixed[] $context
     * @return mixed
     */
    private static function applyFilter(
        ContextAwareFilterInterface $filter,
        string $property,
        ?string $strategy,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?string $operationName,
        array $context
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
        $context['filters'] = [$property => $value];
        $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

        // Save the generated expression from the filter and restore the where clause
        $expression = $queryBuilder->getDQLPart('where');
        $queryBuilder->where($originalWhere);

        return $expression;
    }

    private static function normalizeFilterName(string $className): string
    {
        $classNamespace = explode('\\', $className);
        $shortName = $classNamespace[count($classNamespace) - 1];
        $shortName = StringUtil::removeSuffix($shortName, 'Filter');

        return lcfirst($shortName);
    }
}
