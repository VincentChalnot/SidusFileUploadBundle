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
    public function getPath();

    /**
     * @param string $path
     *
     * @return ResourceInterface
     */
    public function setPath($path);

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

    /**
     * @return string|int
     */
    public function getIdentifier();
}
