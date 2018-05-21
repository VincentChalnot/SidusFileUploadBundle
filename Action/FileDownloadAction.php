<?php

namespace Sidus\FileUploadBundle\Action;

use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Sidus\FileUploadBundle\Stream\FileStreamerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Expose a download link for uploaded resources
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FileDownloadAction
{
    /** @var ResourceManagerInterface */
    protected $resourceManager;

    /** @var FileStreamerInterface */
    protected $fileStreamer;

    /**
     * @param ResourceManagerInterface $resourceManager
     * @param FileStreamerInterface    $fileStreamer
     */
    public function __construct(ResourceManagerInterface $resourceManager, FileStreamerInterface $fileStreamer)
    {
        $this->resourceManager = $resourceManager;
        $this->fileStreamer = $fileStreamer;
    }

    /**
     * @param string     $type
     * @param string|int $identifier
     *
     * @throws \Exception
     *
     * @return StreamedResponse
     */
    public function __invoke($type, $identifier)
    {
        /** @var ResourceInterface $resource */
        $resource = $this->resourceManager->getRepositoryForType($type)->find($identifier);

        return $this->fileStreamer->getStreamedResponse($resource);
    }
}
