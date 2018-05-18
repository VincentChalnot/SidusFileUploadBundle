<?php

namespace Sidus\FileUploadBundle\Stream;

use Sidus\FileUploadBundle\Manager\ResourceManager;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a simple way to stream file to client
 */
class FileStreamer
{
    /** @var ResourceManager */
    protected $resourceManager;

    /**
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @param ResourceInterface $resource
     * @param int               $bufferLength
     *
     * @throws \League\Flysystem\FileNotFoundException
     *
     * @return StreamedResponse
     */
    public function getStreamedResponse(ResourceInterface $resource, $bufferLength = 512)
    {
        $fs = $this->resourceManager->getFilesystem($resource);
        if (!$fs->has($resource->getPath())) {
            throw new NotFoundHttpException("File not found {$resource->getPath()} ({$resource::getType()})");
        }

        $originalFilename = $resource->getPath();
        if ($resource) {
            $originalFilename = $resource->getOriginalFileName();
        }

        $stream = $fs->readStream($resource->getPath());
        if (!$stream) {
            throw new \RuntimeException("Unable to open stream to resource {$resource->getPath()}");
        }

        $response = new StreamedResponse(
            function () use ($stream, $bufferLength) {
                while (!feof($stream)) {
                    echo fread($stream, $bufferLength);
                }
                fclose($stream);
            },
            200
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                'attachment',
                $originalFilename,
                $this->transliterateFilename($originalFilename)
            )
        );
        $response->headers->set('Content-Type', $fs->getMimetype($resource->getPath()));

        return $response;
    }

    /**
     * @param string $originalFilename
     *
     * @return string
     */
    public function transliterateFilename($originalFilename)
    {
        $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
        $string = $transliterator->transliterate($originalFilename);

        return trim(
            preg_replace(
                '/[^\x20-\x7E]/',
                '_',
                $string
            ),
            '_'
        );
    }
}
