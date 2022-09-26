<?php

namespace Instacar\ExtraFiltersBundle\Expression;

interface ExpressionValueProviderInterface
{
    /**
     * @return mixed[]
     */
    public function getValues(): array;
}
