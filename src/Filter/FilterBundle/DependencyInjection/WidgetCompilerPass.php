<?php

namespace Filter\FilterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class WidgetCompilerPass
 * @package Filter\FilterBundle\DependencyInjection
 */
class WidgetCompilerPass implements CompilerPassInterface
{
	/**
	 * @param ContainerBuilder $container
	 */
	public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('filter.widget_collection')) {
            return;
        }

        $definition = $container->getDefinition(
            'filter.widget_collection'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'filter.widget'
        );

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall(
                'add',
                array(new Reference($id))
            );
        }
    }
}