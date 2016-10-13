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
     *
     * @return ResourceInterface
     */
    public function setOriginalFileName($originalFileName);

    /**
     * @return string
     */
    public function getFileName();

    /**
     * @param string $fileName
     *
     * @return ResourceInterface
     */
    public function setFileName($fileName);

    /**
     * @return string
     */
    public function getHash();

    /**
     * @param string $hash
     *
     * @return ResourceInterface
     */
    public function setHash($hash);

    /**
     * @return string
     */
    public static function getType();
}
