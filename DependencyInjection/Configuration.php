<?php

namespace Sidus\FileUploadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link
 * http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /** @var string */
    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root = 'sidus_file_upload')
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);

        $resourceDefinition = $rootNode
            ->children()
            ->arrayNode('configurations')
            ->useAttributeAsKey('code')
            ->prototype('array')
            ->performNoDeepMerging()
            ->children();

        $this->appendResourceDefinition($resourceDefinition);

        $resourceDefinition->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $attributeDefinition
     */
    protected function appendResourceDefinition(NodeBuilder $attributeDefinition)
    {
        $attributeDefinition
            ->scalarNode('entity')->isRequired()->end()
            ->scalarNode('filesystem')->defaultNull()->end()
            ->scalarNode('uploader')->defaultNull()->end();
    }
}
