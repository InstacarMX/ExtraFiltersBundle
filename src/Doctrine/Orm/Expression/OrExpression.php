<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

final class OrExpression extends AbstractDoctrineOrmExpressionProvider
{
    protected static string $name = 'orWhere';

    public function apply(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?Operation $operation): Expr\Orx
    {
        return new Expr\Orx($expressions);
    }
}
