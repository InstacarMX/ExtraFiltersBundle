<?php

namespace Instacar\ExtraFiltersBundle\DependencyInjection;

use ApiPlatform\Doctrine\Orm\Filter as OrmFilter;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Security\Core\Security;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('instacar_extra_filters');
        $rootNode = $treeBuilder->getRootNode();

        $this->addSecuritySection($rootNode);
        $this->addDoctrineOrmSection($rootNode);

        return $treeBuilder;
    }

    private function addSecuritySection(ArrayNodeDefinition $rootNode): void
    {
        $hasSecurity = class_exists(SecurityBundle::class) && interface_exists(Security::class);

        $rootNode
            ->children()
                ->arrayNode('security')
                    ->{$hasSecurity ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addDoctrineOrmSection(ArrayNodeDefinition $rootNode): void
    {
        $hasOrm = class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class);

        $rootNode
            ->children()
                ->arrayNode('doctrine')
                    ->{$hasOrm ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->arrayNode('filters')
                            ->ignoreExtraKeys(false)
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode(OrmFilter\SearchFilter::class)->defaultTrue()->end()
                                ->scalarNode(OrmFilter\RangeFilter::class)->defaultTrue()->end()
                                ->scalarNode(OrmFilter\DateFilter::class)->defaultTrue()->end()
                                ->scalarNode(OrmFilter\BooleanFilter::class)->defaultTrue()->end()
                                ->scalarNode(OrmFilter\NumericFilter::class)->defaultTrue()->end()
                                ->scalarNode(OrmFilter\ExistsFilter::class)->defaultTrue()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
