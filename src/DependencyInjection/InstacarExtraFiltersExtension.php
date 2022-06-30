<?php

namespace Instacar\ExtraFiltersBundle\DependencyInjection;

use Instacar\ExtraFiltersBundle\Doctrine\Orm\Expression\DoctrineOrmExpressionProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class InstacarExtraFiltersExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

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

        $container->setParameter('instacar.extra_filters.doctrine.orm.filters', array_keys(array_filter($config['doctrine']['filters'])));

        $container->registerForAutoconfiguration(DoctrineOrmExpressionProviderInterface::class)
            ->addTag('instacar.extra_filters.doctrine.orm.expression_provider');
    }
}
