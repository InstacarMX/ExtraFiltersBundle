<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Common\Filter;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    /**
     * @param ExpressionFunctionProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        parent::__construct(null, $providers);
    }

    /**
     * @param Expression|string $expression The expression to compile
     * @param mixed[] $names
     * @return string
     */
    public function compile($expression, array $names = []): string
    {
        throw new \RuntimeException('This expression language can not be cached');
    }
}
