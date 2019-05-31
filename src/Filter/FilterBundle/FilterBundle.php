<?php

namespace Filter\FilterBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Filter\FilterBundle\DependencyInjection\WidgetCompilerPass;

/**
 * Class FilterBundle
 * @package Filter\FilterBundle
 */
class FilterBundle extends Bundle
{
	/**
	 * @param ContainerBuilder $container
	 */
	public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new WidgetCompilerPass());
    }
}
