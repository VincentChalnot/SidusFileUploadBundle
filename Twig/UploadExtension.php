<?php

namespace Sidus\FileUploadBundle\Twig;

use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Used to get the public path of a resource from a twig template
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class UploadExtension extends Twig_Extension
{
    /** @var ResourceManagerInterface */
    protected $resourceManager;

    /**
     * @param ResourceManagerInterface $resourceManager
     */
    public function __construct(ResourceManagerInterface $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('resource_path', [$this, 'getResourcePath']),
        ];
    }

    /**
     * @param ResourceInterface $resource
     * @param bool              $absolute
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getResourcePath(ResourceInterface $resource, $absolute = false)
    {
        return $this->resourceManager->getFileUrl($resource, $absolute);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_upload_extension';
    }
}
