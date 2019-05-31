<?php

namespace Filter\FilterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class FilterExtension
 * @package Filter\FilterBundle\DependencyInjection
 */
class FilterExtension extends Extension
{
	/**
	 * @param array $configs
	 * @param ContainerBuilder $container
	 * @throws \Exception
	 */
	public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
