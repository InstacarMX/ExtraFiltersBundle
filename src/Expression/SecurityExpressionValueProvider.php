<?php

namespace Instacar\ExtraFiltersBundle\Expression;

use Symfony\Component\Security\Core\Security;

final class SecurityExpressionValueProvider implements ExpressionValueProviderInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getValues(): array
    {
        return [
            'token' => $this->security->getToken() ?? 'anon',
            'user' => $this->security->getUser() ?? 'anon',
        ];
    }
}
