<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

final class OrExpression extends AbstractDoctrineOrmExpressionProvider
{
    protected static string $name = 'orWhere';

    public function apply(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName): Expr\Orx
    {
        return new Expr\Orx($expressions);
    }
}
