<?php

namespace Sidus\FileUploadBundle\EventListener;

use Exception;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\File\FlysystemFile;
use Oneup\UploaderBundle\Uploader\Response\AbstractResponse;
use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;

/**
 * Handle the upload event, creating the corresponding entity and returning it's reference
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceUploader
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
     * @param PostPersistEvent $event
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return AbstractResponse
     */
    public function onUpload(PostPersistEvent $event)
    {
        $file = $event->getFile();
        if (!$file instanceof FlysystemFile) {
            $class = get_class($file);
            throw new \UnexpectedValueException("Only Flysystem Files are supported, '{$class}' given");
        }

        $originalFilename = $file->getBasename();
        try {
            // Couldn't find anything better with OneUp uploader...
            $originalFiles = $event->getRequest()->files->all();
            if (isset($originalFiles['files'])) {
                $originalFiles = $originalFiles['files'];
                if (count($originalFiles)) {
                    $originalFile = array_pop($originalFiles);
                    $originalFilename = $originalFile->getClientOriginalName();
                }
            }
        } catch (\Exception $e) {
        }

        $file = $this->resourceManager->addFile($file, $originalFilename, $event->getType());

        /** @var AbstractResponse $response */
        $response = $event->getResponse();
        $response[] = $file;

        return $response;
    }
}
