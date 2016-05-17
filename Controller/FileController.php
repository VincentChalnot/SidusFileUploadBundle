<?php

namespace Sidus\FileUploadBundle\Controller;

use Gaufrette\Adapter\Local;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\StreamMode;
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
     * @param string $type
     * @param string $filename
     * @return Response
     * @throws \InvalidArgumentException
     * @throws FileNotFound
     * @throws UnexpectedValueException
     */
    public function downloadAction($type, $filename)
    {
        return $this->getStreamedResponse($type, $filename, true);
    }

    /**
     * @param      $type
     * @param      $filename
     * @param bool $download
     * @return StreamedResponse|NotFoundHttpException
     * @throws UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    protected function getStreamedResponse($type, $filename, $download = false)
    {
        $resourceManager = $this->get('sidus_file_upload.resource.manager');
        $fs = $resourceManager->getFilesystemForType($type);
        if (!$fs->has($filename)) {
            return $this->createNotFoundException("File not found {$filename} ({$type})");
        }

        $originalFilename = $filename;
        $resource = $this->getResource($type, $filename);
        if ($resource) {
            $originalFilename = $resource->getOriginalFileName();
        }

        $stream = $fs->createStream($filename);
        if (!$stream->open(new StreamMode('r'))) {
            throw new UnexpectedValueException("Unable to open stream to file {$filename}");
        }

        $response = new StreamedResponse(function () use ($stream) {
            while (!$stream->eof()) {
                echo $stream->read(512);
            }
            $stream->close();
        }, 200);

        $disposition = 'attachment';
        $adapter = $fs->getAdapter();
        if ($adapter instanceof Local && !$download) {
            $mimeType = $adapter->mimeType($filename);
            if ($mimeType) {
                $response->headers->set('Content-Type', $mimeType);
                $disposition = 'inline';
            }
        }
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition($disposition, $originalFilename)
        );

        return $response;
    }

    /**
     * @param string $type
     * @param string $filename
     * @return ResourceInterface|null
     * @throws \UnexpectedValueException
     */
    protected function getResource($type, $filename)
    {
        $resourceManager = $this->get('sidus_file_upload.resource.manager');
        $resourceConfiguration = $resourceManager->getResourceTypeConfiguration($type);

        return $this->get('doctrine')->getRepository($resourceConfiguration->getEntity())->findOneBy([
            'fileName' => $filename,
        ]);
    }
}
