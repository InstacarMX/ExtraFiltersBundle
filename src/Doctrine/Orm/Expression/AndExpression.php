<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

final class AndExpression extends AbstractDoctrineOrmExpressionProvider
{
    protected static string $name = 'andWhere';

    public function apply(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?Operation $operation): Expr\Andx
    {
        return new Expr\Andx($expressions);
    }
}
