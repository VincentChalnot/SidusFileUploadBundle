<?php

namespace Sidus\FileUploadBundle\Model;

/**
 * Interface for uploaded resources
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
    public static function getType();
}
