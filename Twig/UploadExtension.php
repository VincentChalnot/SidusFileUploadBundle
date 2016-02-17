<?php

namespace Sidus\FileUploadBundle\Twig;

use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig_Extension;
use Twig_SimpleFunction;

class UploadExtension extends Twig_Extension
{
    /** @var AssetExtension */
    protected $assetExtension;

    /**
     * UploadExtension constructor.
     * @param AssetExtension $assetExtension
     */
    public function __construct(AssetExtension $assetExtension)
    {
        $this->assetExtension = $assetExtension;
    }

    public function resource_path(ResourceInterface $resource = null, $absolute = false)
    {
        $fileName = '';
        if ($resource) {
            $fileName = $resource->getFileName();
        }
        return $this->assetExtension->getAssetUrl('resources/images/' . $fileName, null, $absolute);
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('resource_path', [$this, 'resource_path'])
        ];
    }

    public function getName()
    {
        return 'sidus_upload_extension';
    }
}
