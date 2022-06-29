<?php

namespace Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use Instacar\ExtraFiltersBundle\Doctrine\Common\Filter\AbstractExpressionLanguageFactory;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ExpressionLanguageFactory extends AbstractExpressionLanguageFactory
{
    protected function supports(FilterInterface $filter): bool
    {
        return $filter instanceof ContextAwareFilterInterface;
    }

    /**
     * @param ContextAwareFilterInterface $filter
     * @return ExpressionFunctionProviderInterface
     */
    protected function createFilterProvider(FilterInterface $filter): ExpressionFunctionProviderInterface
    {
        return new ExpressionProviderFilterAdapter($filter);
    }
}
