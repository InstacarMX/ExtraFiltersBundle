<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

abstract class AbstractDoctrineOrmExpressionProvider implements ExpressionFunctionProviderInterface
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
                        $arguments['operation'],
                    );
                },
            ),
        ];
    }

    /**
     * @param array<Expr\Andx|Expr\Orx|Expr\Comparison|Expr\Func> $expressions
     * @param QueryBuilder $queryBuilder
     * @param string $resourceClass
     * @param Operation|null $operation
     * @return mixed
     */
    abstract public function apply(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?Operation $operation): mixed;
}
