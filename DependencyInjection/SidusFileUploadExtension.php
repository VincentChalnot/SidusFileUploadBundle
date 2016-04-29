<?php

namespace Sidus\FileUploadBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SidusFileUploadExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $managerDefinition = $container->getDefinition('sidus_file_upload.resource.manager');

        // Automatically declare a service for each attribute configured
        foreach ($config['configurations'] as $code => $resourceConfiguration) {
            if (!isset($resourceConfiguration['filesystem_key'])) {
                $resourceConfiguration['filesystem_key'] = $config['filesystem_key'];
            }
            $managerDefinition->addMethodCall('addResourceConfiguration', [$code, $resourceConfiguration]);
        }
    }
}
