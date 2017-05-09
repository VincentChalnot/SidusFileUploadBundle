<?php

namespace Sidus\FileUploadBundle\Registry;

use League\Flysystem\FilesystemInterface;

/**
 * Allow access to all Flysystem's filesystems through a single registry
 */
class FilesystemRegistry
{
    /** @var FilesystemInterface[] */
    protected $filesystems = [];

    /**
     * @return FilesystemInterface[]
     */
    public function getFilesystems()
    {
        return $this->filesystems;
    }

    /**
     * @param string              $code
     * @param FilesystemInterface $filesystem
     */
    public function addFilesystem($code, FilesystemInterface $filesystem)
    {
        $this->filesystems[$code] = $filesystem;
    }

    /**
     * @param string $code
     *
     * @throws \UnexpectedValueException
     *
     * @return FilesystemInterface
     */
    public function getFilesystem($code)
    {
        if (!$this->hasFilesystem($code)) {
            throw new \UnexpectedValueException("No filesystem with code : {$code}");
        }

        return $this->filesystems[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasFilesystem($code)
    {
        return array_key_exists($code, $this->filesystems);
    }
}
