<?php

namespace Sidus\FileUploadBundle\Metadata;

use League\Flysystem\File;
use Sidus\FileUploadBundle\Model\ResourceInterface;

/**
 * Allows custom
 */
interface MetadataUpdaterInterface
{
    /**
     * Allow to update the resource based on custom logic
     * Should be handled in an event
     *
     * @param ResourceInterface $resource
     * @param File              $file
     */
    public function updateResourceMetadata(ResourceInterface $resource, File $file);
}
