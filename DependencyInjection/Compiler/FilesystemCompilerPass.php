<?php

namespace Sidus\FileUploadBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Inject Flysystem's filesytems into a single registry
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FilesystemCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $registry;

    /** @var string */
    protected $tag;

    /**
     * @param string $registry
     * @param string $tag
     */
    public function __construct($registry, $tag)
    {
        $this->registry = $registry;
        $this->tag = $tag;
    }

    /**
     * Inject tagged services into defined registry
     *
     * @api
     *
     * @param ContainerBuilder $container
     *
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has($this->registry)) {
            return;
        }

        $definition = $container->findDefinition($this->registry);
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        foreach ($taggedServices as $id => $tags) {
            $code = preg_replace('/^oneup_flysystem.(.+)_filesystem$/', '$1', $id);
            $definition->addMethodCall(
                'addFilesystem',
                [$code, new Reference($id)]
            );
        }
    }
}
