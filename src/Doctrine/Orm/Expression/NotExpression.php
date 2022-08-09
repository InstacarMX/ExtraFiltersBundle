<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

final class NotExpression extends AbstractDoctrineOrmExpressionProvider
{
    protected static string $name = 'notWhere';

    public function apply(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?Operation $operation): Expr\Func
    {
        return new Expr\Func('NOT', $expressions);
    }
}
