<?php

namespace Sidus\FileUploadBundle\Configuration;

class ResourceTypeConfiguration
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $entity;

    /** @var array */
    protected $uploadConfig;

    /**
     * @param $code
     * @param string $entity
     * @param $uploadConfig
     */
    public function __construct($code, $entity, $uploadConfig)
    {
        $this->code = $code;
        $this->entity = $entity;
        $this->uploadConfig = $uploadConfig;
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
     * @return mixed
     */
    public function getUploadConfig()
    {
        return $this->uploadConfig;
    }

    /**
     * @return string
     */
    public function getEndPoint()
    {
        return $this->code;
    }
}
