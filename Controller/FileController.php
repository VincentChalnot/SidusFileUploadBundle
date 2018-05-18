<?php

namespace Sidus\FileUploadBundle\Controller;

use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

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
        $resource = $this->get('sidus_file_upload.resource.manager')->getRepositoryForType($type)->find($identifier);

        return $this->get('sidus_file_upload.file_streamer')->getStreamedResponse($resource);
    }
}
