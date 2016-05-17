<?php

namespace Sidus\FileUploadBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Automatically add the file-upload widget form in the twig templates
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FormPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        $template = 'SidusFileUploadBundle:Form:fields.html.twig';
        $resources = $container->getParameter('twig.form.resources');
        // Ensure it wasn't already added via config
        if (!in_array($template, $resources, true)) {
            $resources[] = $template;
            $container->setParameter('twig.form.resources', $resources);
        }
    }
}
