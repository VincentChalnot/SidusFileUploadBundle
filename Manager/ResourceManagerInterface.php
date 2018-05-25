<?php

namespace Sidus\FileUploadBundle\Manager;

use League\Flysystem\File;
use League\Flysystem\FilesystemInterface;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Sidus\FileUploadBundle\Model\ResourceRepositoryInterface;
use UnexpectedValueException;

/**
 * Used to work with resources
 */
interface ResourceManagerInterface
{
    /**
     * Add an entry for Resource entity in database at each upload
     * OR: find the already uploaded file based on it's hash
     *
     * @param File   $file
     * @param string $originalFilename
     * @param string $type
     *
     * @throws \InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return ResourceInterface
     */
    public function addFile(File $file, $originalFilename, $type = null);

    /**
     * Remove a Resource from the hard drive
     * DOES NOT REMOVE THE ENTITY
     *
     * @param ResourceInterface $resource
     */
    public function removeResourceFile(ResourceInterface $resource);

    /**
     * Get the url of a "Resource" (for the web)
     *
     * @param ResourceInterface $resource
     * @param bool              $absolute
     *
     * @return string
     */
    public function getFileUrl(ResourceInterface $resource, $absolute = false);

    /**
     * @param ResourceInterface $resource
     *
     * @return FilesystemInterface
     */
    public function getFilesystem(ResourceInterface $resource);

    /**
     * @param string $type
     *
     * @return FilesystemInterface
     */
    public function getFilesystemForType($type);

    /**
     * Get the path for an uploaded file, does not check if file exists
     *
     * @param ResourceInterface $resource
     *
     * @return File
     */
    public function getFile(ResourceInterface $resource);

    /**
     * @return ResourceTypeConfiguration[]
     */
    public function getResourceConfigurations();

    /**
     * @param string $type
     *
     * @return ResourceTypeConfiguration
     */
    public function getResourceTypeConfiguration($type);

    /**
     * @param string $code
     * @param array  $resourceConfiguration
     */
    public function addResourceConfiguration($code, array $resourceConfiguration);

    /**
     * @param string $type
     *
     * @return ResourceRepositoryInterface
     */
    public function getRepositoryForType($type);
}
