<?php

namespace Sidus\FileUploadBundle;

use Sidus\FileUploadBundle\DependencyInjection\Compiler\FilesystemCompilerPass;
use Sidus\FileUploadBundle\DependencyInjection\Compiler\FormPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusFileUploadBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FormPass());
        $container->addCompilerPass(new FilesystemCompilerPass(
            'sidus_file_upload.registry.filesystem',
            'oneup_flysystem.filesystem'
        ));
    }
}
