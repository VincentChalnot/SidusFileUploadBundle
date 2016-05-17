<?php

namespace Sidus\FileUploadBundle\Twig;

use Sidus\FileUploadBundle\Manager\ResourceManager;
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
    /** @var ResourceManager */
    protected $resourceManager;

    /**
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
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
     * @param ResourceInterface|null $resource
     * @param string                 $action
     * @param bool                   $absolute
     * @return string
     * @throws \Exception
     */
    public function getResourcePath(ResourceInterface $resource = null, $action = 'download', $absolute = false)
    {
        return $this->resourceManager->getFileUrl($resource, $action, $absolute);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_upload_extension';
    }
}
