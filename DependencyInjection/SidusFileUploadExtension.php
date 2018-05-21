<?php

namespace Sidus\FileUploadBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SidusFileUploadExtension extends Extension
{
    /**
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('action.yml');
        $loader->load('controllers.yml');
        $loader->load('deprecated.yml');
        $loader->load('events.yml');
        $loader->load('forms.yml');
        $loader->load('managers.yml');
        $loader->load('registry.yml');
        $loader->load('stream.yml');
        $loader->load('twig.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $managerDefinition = $container->getDefinition(ResourceManagerInterface::class);

        // Automatically declare a service for each attribute configured
        foreach ($config['configurations'] as $code => $resourceConfiguration) {
            $managerDefinition->addMethodCall('addResourceConfiguration', [$code, $resourceConfiguration]);
        }
    }
}
