<?php

namespace Sidus\FileUploadBundle\Stream;

use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a resource over a HttpResponse
 */
interface FileStreamerInterface
{
    /**
     * @param ResourceInterface $resource
     * @param int               $bufferLength
     *
     * @return StreamedResponse
     */
    public function getStreamedResponse(ResourceInterface $resource, $bufferLength = 512);
}
