<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Generator;

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait as OrmPropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

abstract class AbstractDoctrineOrmGenerator implements ExpressionFunctionProviderInterface, DoctrineOrmGeneratorInterface
{
    use OrmPropertyHelperTrait;
    use PropertyHelperTrait;

    protected static string $name;

    protected ManagerRegistry $managerRegistry;
    protected LoggerInterface $logger;

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                static::$name,
                static function (string $property, ?string $strategy = null, array $parameters = []) {
                    return sprintf('%s(%s)', static::$name, implode([$property, $strategy, ...$parameters]));
                },
                function ($arguments, string $property, ?string $strategy = null, array $parameters = []) {
                    return $this->process(
                        $property,
                        $strategy,
                        $parameters,
                        $arguments['value'],
                        $arguments['queryBuilder'],
                        $arguments['queryNameGenerator'],
                        $arguments['resourceClass'],
                        $arguments['operationName'],
                    );
                },
            ),
        ];
    }

    /**
     * @param string $property
     * @param string|null $strategy
     * @param mixed[] $parameters
     * @param mixed $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param string|null $operationName
     * @return Expr\Comparison|Expr\Func|Expr\Orx|null
     */
    abstract public function process(
        string $property,
        ?string $strategy,
        array $parameters,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?string $operationName
    );

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
