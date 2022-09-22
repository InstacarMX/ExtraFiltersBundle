<?php

namespace Instacar\ExtraFiltersBundle\DependencyInjection\Compiler;

use ApiPlatform\Util\Inflector;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter\ExpressionFilter as OrmExpressionFilter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class ExpressionFilterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $availableFilters = [
            OrmExpressionFilter::class => $container->getParameter('instacar.extra_filters.doctrine.orm.filters'),
        ];

        foreach ($container->findTaggedServiceIds('api_platform.filter', true) as $serviceId => $tags) {
            $serviceDefinition = $container->getDefinition($serviceId);
            $serviceClass = $serviceDefinition instanceof ChildDefinition
                ? $serviceDefinition->getParent()
                : $serviceDefinition->getClass();

            if ($serviceClass !== OrmExpressionFilter::class) {
                continue;
            }

            try {
                $allowedFilters = $serviceDefinition->getArgument('$filters');
            } catch (\OutOfBoundsException $e) {
                $allowedFilters = $availableFilters[$serviceClass];
            }

            if (empty($allowedFilters)) {
                throw new \InvalidArgumentException('You must provide filters for the ExpressionFilter');
            }

            $filters = [];
            foreach ($allowedFilters as $filterClass) {
                $id = $serviceId . '_' . Inflector::tableize(str_replace('\\', '', $filterClass));

                if ($container->has($id)) {
                    continue;
                }

                if (null === $filterReflectionClass = $container->getReflectionClass($filterClass, false)) {
                    throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $filterClass, $id));
                }

                if ($container->has($filterClass) && ($parentDefinition = $container->findDefinition($filterClass))->isAbstract()) {
                    $definition = new ChildDefinition($parentDefinition->getClass());
                } else {
                    $definition = new Definition($filterReflectionClass->getName());
                    $definition->setAutoconfigured(true);
                }

                $definition->setAutowired(true);

                $filters[] = new Reference($id);
                $container->setDefinition($id, $definition);
            }

            if ($serviceClass === OrmExpressionFilter::class) {
                $expressionLanguageDefinition = new ChildDefinition('instacar.extra_filters.orm.expression_language');

                $serviceDefinition->setArgument('$expressionLanguage', $expressionLanguageDefinition);
                $serviceDefinition->setArgument('$filters', $filters);
            }
        }
    }
}
