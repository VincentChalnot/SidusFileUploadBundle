<?php

namespace Sidus\FileUploadBundle\Configuration;

/**
 * Resource type configuration as a service, handles the link between a Doctrine entity,
 * an upload endpoint and a filesystem
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceTypeConfiguration
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $entity;

    /** @var string */
    protected $filesystemKey;

    /** @var string */
    protected $endpoint;

    /**
     * @param string $code
     * @param array  $configuration
     */
    public function __construct($code, array $configuration)
    {
        $this->code = $code;
        $this->entity = $configuration['entity'];
        $this->filesystemKey = $configuration['filesystem'] ?: $code;
        $this->endpoint = $configuration['uploader'] ?: $code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getFilesystemKey()
    {
        return $this->filesystemKey;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }
}
