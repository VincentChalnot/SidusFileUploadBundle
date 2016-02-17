<?php

namespace Sidus\FileUploadBundle\Twig;

use Sidus\FileUploadBundle\Manager\ResourceManager;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Twig_Extension;
use Twig_SimpleFunction;

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

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('resource_path', [$this, 'resource_path'])
        ];
    }

    public function resource_path(ResourceInterface $resource = null, $action = 'download', $absolute = false)
    {
        return $this->resourceManager->getFileUrl($resource, $action, $absolute);
    }

    public function getName()
    {
        return 'sidus_upload_extension';
    }
}
