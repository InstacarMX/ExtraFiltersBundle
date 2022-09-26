<?php

namespace Instacar\ExtraFiltersBundle\Expression;

use Instacar\ExtraFiltersBundle\Util\IterableUtil;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

final class ExpressionLanguage extends BaseExpressionLanguage
{
    /**
     * @var ExpressionValueProviderInterface[]
     */
    private array $valueProviders;

    /**
     * @param iterable<ExpressionFunctionProviderInterface> $functionProviders
     * @param iterable<ExpressionValueProviderInterface> $valueProviders
     */
    public function __construct(iterable $functionProviders, iterable $valueProviders)
    {
        $this->valueProviders = IterableUtil::iterableToArray($valueProviders);

        parent::__construct(null, IterableUtil::iterableToArray($functionProviders));
    }

    /**
     * @param Expression|string $expression
     * @param mixed[] $values
     * @return mixed
     */
    public function evaluate(Expression|string $expression, array $values = []): mixed
    {
        foreach ($this->valueProviders as $valueProvider) {
            array_push($values, ...$valueProvider->getValues());
        }

        return parent::evaluate($expression, $values);
    }

    /**
     * @param Expression|string $expression The expression to compile
     * @param mixed[] $names
     * @return string
     */
    public function compile(Expression|string $expression, array $names = []): string
    {
        throw new \RuntimeException('This expression language can not be cached');
    }
}
