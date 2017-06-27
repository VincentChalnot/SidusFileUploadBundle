<?php

namespace Sidus\FileUploadBundle\Controller;

use Oneup\UploaderBundle\Controller\BlueimpController as BaseBlueimpController;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File as HttpFile;

/**
 * BlueimpController override to allow manual upload of a file (through a service/command for example)
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class BlueimpController extends BaseBlueimpController
{
    /** @var Request */
    protected $request;

    /**
     * @param FileInterface|HttpFile $file
     * @param ResponseInterface      $response
     * @param Request|null           $request
     *
     * @throws \UnexpectedValueException
     *
     * @return ResourceInterface
     */
    public function handleManualUpload($file, ResponseInterface $response, Request $request = null)
    {
        if (!$request) {
            $request = new Request();
        }
        $this->setRequest($request);
        $this->handleUpload($file, $response, $request);
        $files = $response->assemble();
        if (0 === count($files)) {
            throw new \UnexpectedValueException('File upload returned empty response');
        }

        return array_pop($files);
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    protected function getRequest()
    {
        if ($this->request) {
            return $this->request;
        }

        return parent::getRequest();
    }
}
