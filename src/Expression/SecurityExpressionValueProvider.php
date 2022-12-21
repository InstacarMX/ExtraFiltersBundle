<?php

namespace Instacar\ExtraFiltersBundle\Expression;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Security as LegacySecurity;

final class SecurityExpressionValueProvider implements ExpressionValueProviderInterface
{
    private Security|LegacySecurity $security;

    public function __construct(Security|LegacySecurity $security)
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
