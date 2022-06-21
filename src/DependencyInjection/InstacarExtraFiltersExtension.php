<?php

namespace Instacar\ExtraFiltersBundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Configuration;
use Instacar\ExtraFiltersBundle\Doctrine\Common\Filter\ExpressionLanguage;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression\DoctrineOrmExpressionInterface;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter\ExpressionFilter;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Generator\DoctrineOrmGeneratorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class InstacarExtraFiltersExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->registerDoctrineOrmConfiguration($container, $config, $loader);
    }

    /**
     * @param ContainerBuilder $container
     * @param mixed[] $config
     * @param XmlFileLoader $loader
     * @return void
     * @throws \Exception
     */
    private function registerDoctrineOrmConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['doctrine'])) {
            return;
        }

        $loader->load('orm.xml');

        $container->registerForAutoconfiguration(DoctrineOrmGeneratorInterface::class)
            ->addTag('instacar.extra_filters.doctrine.orm.expression_provider');
        $container->registerForAutoconfiguration(DoctrineOrmExpressionInterface::class)
            ->addTag('instacar.extra_filters.doctrine.orm.expression_provider');
        $container->registerForAutoconfiguration(ExpressionFilter::class)
            ->setBindings([
                ExpressionLanguage::class => $container->getDefinition('instacar.extra_filters.orm.expression_language'),
            ]);
    }
}
