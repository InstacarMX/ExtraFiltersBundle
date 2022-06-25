<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class AndExpression extends AbstractDoctrineOrmExpression
{
    protected static string $name = 'andWhere';

    public function process(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName): Expr\Andx
    {
        return new Expr\Andx($expressions);
    }
}
