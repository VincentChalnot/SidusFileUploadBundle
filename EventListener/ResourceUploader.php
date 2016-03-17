<?php

namespace Sidus\FileUploadBundle\EventListener;

use Exception;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\File\GaufretteFile;
use Oneup\UploaderBundle\Uploader\Response\AbstractResponse;
use Sidus\FileUploadBundle\Manager\ResourceManager;

class ResourceUploader
{
    /** @var ResourceManager */
    protected $resourceManager;

    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @param PostPersistEvent $event
     * @return AbstractResponse
     * @throws \UnexpectedValueException|\InvalidArgumentException
     */
    public function onUpload(PostPersistEvent $event)
    {
        $file     = $event->getFile();
        if (!$file instanceof GaufretteFile) {
            $class = get_class($file);
            throw new \UnexpectedValueException("Only gaufrette files are supported, '{$class}' given");
        }

        $originalFilename = $file->getName();
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
