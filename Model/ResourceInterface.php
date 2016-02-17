<?php

namespace Sidus\FileUploadBundle\Model;

/**
 * Uploaded resource
 */
interface ResourceInterface
{
    /**
     * @return string
     */
    public function getOriginalFileName();

    /**
     * @param string $originalFileName
     * @return $this
     */
    public function setOriginalFileName($originalFileName);

    /**
     * @return string
     */
    public function getFileName();

    /**
     * @param string $fileName
     * @return $this
     */
    public function setFileName($fileName);

    /**
     * @return string
     */
    public function getType();
}
