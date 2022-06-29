<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\DoctrineOrmExpressionProviderInterface;
use Instacar\ExtraFiltersBundle\Util\StringUtil;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class ExpressionProviderFilterAdapter implements DoctrineOrmExpressionProviderInterface
{
    private ContextAwareFilterInterface $filter;

    public function __construct(ContextAwareFilterInterface $filter)
    {
        $this->filter = $filter;
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                self::normalizeFilterName(get_class($this->filter)),
                static function () {
                    // Uncompilable
                },
                function ($arguments, string $property = null, $value = null) {
                    return $this->process(
                        $property ?? $arguments['property'],
                        $value ?? $arguments['value'],
                        $arguments['queryBuilder'],
                        $arguments['queryNameGenerator'],
                        $arguments['resourceClass'],
                        $arguments['operationName'],
                        $arguments['context'],
                    );
                },
            ),
        ];
    }

    /**
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
        $this->filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

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
