<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

abstract class AbstractDoctrineOrmExpression implements ExpressionFunctionProviderInterface, DoctrineOrmExpressionInterface
{
    protected static string $name;

    protected ManagerRegistry $managerRegistry;
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                static::$name,
                static function (...$expressions) {
                    return sprintf('%s(%s)', self::$name, implode($expressions));
                },
                function ($arguments, ...$expressions) {
                    return $this->process(
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
    abstract public function process(array $expressions, QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName);

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
