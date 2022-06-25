<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class NotExpression extends AbstractDoctrineOrmExpression
{
    protected static string $name = 'notWhere';

    public function process(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName): Expr\Func
    {
        return new Expr\Func('NOT', $expressions);
    }
}
