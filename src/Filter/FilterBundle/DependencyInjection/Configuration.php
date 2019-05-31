<?php

namespace Filter\FilterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Filter\FilterBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * @return TreeBuilder
	 */
	public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('filter');

        return $treeBuilder;
    }
}
