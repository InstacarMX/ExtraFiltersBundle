<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\DoctrineOrmExpressionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

abstract class AbstractDoctrineOrmExpressionProvider implements DoctrineOrmExpressionProviderInterface
{
    protected static string $name;

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                static::$name,
                static function () {
                    // Uncompilable
                },
                function ($arguments, ...$expressions) {
                    return $this->apply(
                        $expressions,
                        $arguments['queryBuilder'],
                        $arguments['resourceClass'],
                        $arguments['operationName'],
                    );
                },
            ),
        ];
    }

    /**
     * @param array<Expr\Andx|Expr\Orx|Expr\Comparison|Expr\Func> $expressions
     * @param QueryBuilder $queryBuilder
     * @param string $resourceClass
     * @param string|null $operationName
     * @return mixed
     */
    abstract public function apply(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName);
}
