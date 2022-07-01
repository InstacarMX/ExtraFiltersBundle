<?php

namespace Instacar\ExtraFiltersBundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter as OrmFilter;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('instacar_extra_filters');
        $rootNode = $treeBuilder->getRootNode();

        $this->addDoctrineOrmSection($rootNode);

        return $treeBuilder;
    }

    private function addDoctrineOrmSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('doctrine')
                    ->{class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
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
