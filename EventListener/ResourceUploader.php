<?php

namespace Sidus\FileUploadBundle\EventListener;

use Exception;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\File\GaufretteFile;
use Sidus\FileUploadBundle\Manager\ResourceManager;

class ResourceUploader
{
    /**
     *
     * @var ResourceManager
     */
    protected $resourceManager;

    public function __construct($resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    public function onUpload(PostPersistEvent $event)
    {
        $file     = $event->getFile();
        if (!$file instanceof GaufretteFile) {
            $class = get_class($file);
            throw new \UnexpectedValueException("Only gaufrette files are accepted {$class} given");
        }
        $response = $event->getResponse();

        try {
            // Couldn't find anyting better with oneup uploader...
            $originalFiles = $event->getRequest()->files->all()['files'];
            $originalFilename = array_pop($originalFiles)->getClientOriginalName();
        } catch (\Exception $e) {
            $originalFilename = $file->getName();
        }

        $file = $this->resourceManager->addFile($file, $originalFilename, $event->getType());
        $this->resourceManager->cleanUploads();

        $response[] = $file;
    }
}
