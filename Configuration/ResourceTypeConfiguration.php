<?php

namespace Sidus\FileUploadBundle\Configuration;

use Gaufrette\Filesystem;

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
     * @param $configuration
     */
    public function __construct($code, array $configuration)
    {
        $this->code = $code;
        $this->entity = $configuration['entity'];
        $this->filesystemKey = $configuration['filesystem_key'];
        $this->endpoint = isset($configuration['endpoint']) ? $configuration['endpoint'] : $code;
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
     * @return Filesystem
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
