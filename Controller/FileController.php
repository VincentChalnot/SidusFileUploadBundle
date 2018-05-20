<?php

namespace Sidus\FileUploadBundle\Controller;

use Sidus\FileUploadBundle\Model\ResourceInterface;
use Sidus\FileUploadBundle\Stream\FileStreamerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;

/**
 * Expose a download link for uploaded resources
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FileController extends Controller
{
    /**
     * @param string     $type
     * @param string|int $identifier
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function downloadAction($type, $identifier)
    {
        /** @var ResourceInterface $resource */
        $resource = $this->get(ResourceManagerInterface::class)->getRepositoryForType($type)->find($identifier);

        return $this->get(FileStreamerInterface::class)->getStreamedResponse($resource);
    }
}
