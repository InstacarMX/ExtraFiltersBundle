<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class OrExpression extends AbstractDoctrineOrmExpression
{
    static protected string $name = 'orWhere';

    public function process(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName): Expr\Orx
    {
        return new Expr\Orx($expressions);
    }
}
