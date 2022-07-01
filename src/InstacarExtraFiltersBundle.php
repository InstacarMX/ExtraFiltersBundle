<?php

namespace Instacar\ExtraFiltersBundle;

use Instacar\ExtraFiltersBundle\DependencyInjection\Compiler\ExpressionFilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class InstacarExtraFiltersBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ExpressionFilterPass());
    }
}
