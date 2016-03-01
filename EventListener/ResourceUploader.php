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

        $originalFilename = $file->getName();
        try {
            // Couldn't find anyting better with oneup uploader...
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
        $this->resourceManager->cleanUploads();

        $response[] = $file;
    }
}
