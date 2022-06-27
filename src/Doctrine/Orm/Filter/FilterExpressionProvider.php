<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class FilterExpressionProvider implements ExpressionFunctionProviderInterface
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
        return array_map(function (ContextAwareFilterInterface $filter) {
            $name = self::normalizeFilterName(get_class($filter));

            return new ExpressionFunction(
                $name,
                function (string $property, ?string $strategy = null, array $parameters = []) use ($name) {
                    return sprintf('%s(%s)', $name, implode([$property, $strategy, ...$parameters]));
                },
                function ($arguments, string $property) use ($filter) {
                    return $this->process(
                        $filter,
                        $property,
                        $arguments['value'],
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
    private function process(
        ContextAwareFilterInterface $filter,
        string $property,
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
        $prefix = 'filter';
        $classNamespace = explode('\\', $className);
        $className = strtolower($classNamespace[count($classNamespace) - 1]);
        if (str_ends_with($className, $prefix)) {
            return substr($className, 0, strlen($className) - strlen($prefix));
        }

        return $className;
    }
}
