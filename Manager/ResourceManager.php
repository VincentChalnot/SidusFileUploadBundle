<?php

namespace Sidus\FileUploadBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use League\Flysystem\File;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Entity\ResourceRepository;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Sidus\FileUploadBundle\Registry\FilesystemRegistry;
use Symfony\Component\Routing\RouterInterface;
use UnexpectedValueException;

/**
 * Manage access to resources: entities and files
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceManager
{
    /** @var ResourceTypeConfiguration[] */
    protected $resourceConfigurations;

    /** @var Registry */
    protected $doctrine;

    /** @var LoggerInterface */
    protected $logger;

    /** @var FilesystemRegistry */
    protected $filesystemRegistry;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param Registry           $doctrine
     * @param LoggerInterface    $logger
     * @param FilesystemRegistry $filesystemRegistry
     * @param RouterInterface    $router
     */
    public function __construct(
        Registry $doctrine,
        LoggerInterface $logger,
        FilesystemRegistry $filesystemRegistry,
        RouterInterface $router
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->filesystemRegistry = $filesystemRegistry;
        $this->router = $router;
    }

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
    public function addFile(File $file, $originalFilename, $type = null)
    {
        $fs = $this->getFilesystemForType($type);

        $hash = $fs->hash($file->getPath());
        $resource = $this->findByHash($type, $hash);

        if ($resource) { // If resource already uploaded
            if ($fs->has($resource->getPath())) { // If the file is still there
                $file->delete(); // Delete uploaded file (because we already have one)

                return $resource;
            }
        } else {
            $resource = $this->createByType($type);
        }

        $resource
            ->setOriginalFileName($originalFilename)
            ->setPath($file->getPath())
            ->setHash($hash);

        $this->updateResourceMetadata($resource, $file);

        $em = $this->doctrine->getManager();
        $em->persist($resource);
        $em->flush();

        return $resource;
    }

    /**
     * Remove a Resource from the hard drive
     * DOES NOT REMOVE THE ENTITY
     *
     * @param ResourceInterface $resource
     *
     * @throws UnexpectedValueException
     */
    public function removeResourceFile(ResourceInterface $resource)
    {
        $fs = $this->getFilesystem($resource);
        try {
            $fs->delete($resource->getPath());
        } catch (FileNotFoundException $e) {
            $this->logger->warning(
                "Tried to remove missing file {$resource->getPath()} ({$resource->getOriginalFileName()})"
            );
        }
    }

    /**
     * Get the url of a "Resource" (for the web)
     *
     * @param ResourceInterface $resource
     * @param bool              $absolute
     *
     * @return string
     * @throws \Exception
     */
    public function getFileUrl(ResourceInterface $resource, $absolute = false)
    {
        return $this->router->generate(
            'sidus_file_upload.file.download',
            [
                'type' => $resource->getType(),
                'identifier' => $resource->getIdentifier(),
            ],
            $absolute
        );
    }

    /**
     * @param ResourceInterface $resource
     *
     * @throws UnexpectedValueException
     *
     * @return FilesystemInterface
     */
    public function getFilesystem(ResourceInterface $resource)
    {
        return $this->getFilesystemForType($resource->getType());
    }

    /**
     * @param string $type
     *
     * @throws UnexpectedValueException
     *
     * @return FilesystemInterface
     */
    public function getFilesystemForType($type)
    {
        $config = $this->getResourceTypeConfiguration($type);

        return $this->filesystemRegistry->getFilesystem($config->getFilesystemKey());
    }

    /**
     * Get the path for an uploaded file, does not check if file exists
     *
     * @param ResourceInterface $resource
     *
     * @throws \UnexpectedValueException
     *
     * @return File
     */
    public function getFile(ResourceInterface $resource)
    {
        $fs = $this->getFilesystem($resource);
        if (!$fs->has($resource->getPath())) {
            return false;
        }

        return $fs->get($resource->getPath());
    }

    /**
     * @return ResourceTypeConfiguration[]
     */
    public function getResourceConfigurations()
    {
        return $this->resourceConfigurations;
    }

    /**
     * @param string $type
     *
     * @return ResourceTypeConfiguration
     * @throws UnexpectedValueException
     */
    public function getResourceTypeConfiguration($type)
    {
        if (!isset($this->resourceConfigurations[$type])) {
            throw new UnexpectedValueException("Unknown resource type '{$type}'");
        }

        return $this->resourceConfigurations[$type];
    }

    /**
     * @param string $code
     * @param array  $resourceConfiguration
     */
    public function addResourceConfiguration($code, array $resourceConfiguration)
    {
        $object = new ResourceTypeConfiguration($code, $resourceConfiguration);
        $this->resourceConfigurations[$code] = $object;
    }

    /**
     * @param string $type
     *
     * @throws \UnexpectedValueException
     *
     * @return ResourceRepository
     */
    public function getRepositoryForType($type)
    {
        $class = $this->getResourceTypeConfiguration($type)->getEntity();

        return $this->doctrine->getRepository($class);
    }

    /**
     * @param $type
     *
     * @return ResourceInterface
     * @throws UnexpectedValueException
     */
    protected function createByType($type)
    {
        $entity = $this->getResourceTypeConfiguration($type)->getEntity();

        return new $entity();
    }

    /**
     * Allow to update the resource based on custom logic
     * Should be handled in an event
     *
     * @param ResourceInterface $resource
     * @param File              $file
     */
    protected function updateResourceMetadata(ResourceInterface $resource, File $file)
    {
        // Custom logic
    }

    /**
     * @param string $type
     * @param string $hash
     *
     * @throws \UnexpectedValueException
     *
     * @return ResourceInterface
     */
    protected function findByHash($type, $hash)
    {
        return $this->getRepositoryForType($type)->findOneBy(['hash' => $hash]);
    }
}
