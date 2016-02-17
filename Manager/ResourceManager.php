<?php

namespace Sidus\FileUploadBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Psr\Log\LoggerInterface;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use UnexpectedValueException;

class ResourceManager
{
    const BASE_RESOURCE = 'Sidus\FileUploadBundle\Entity\Resource';

    /** @var ResourceTypeConfiguration[] */
    protected $resourceConfigurations;

    /** @var Registry */
    protected $doctrine;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param Registry $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct(Registry $doctrine, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * @todo REFACTOR
     */
    public function cleanUploads()
    {
//        $em = $this->getEntityManager();
//        $orphans = $em->getRepository('SidusCoreBundle:Editor\Image')->findOrphans();
//        foreach ($orphans as $orphan) {
//            $em->remove($orphan);
//        }
    }

    /**
     * Add an entry for Resource entity in database at each upload
     *
     * @param File $file
     * @param string $originalFilename
     * @param string $type
     * @return ResourceInterface
     * @throws \InvalidArgumentException
     */
    public function addFile(File $file, $originalFilename, $type = null)
    {
        $resource = $this->createByType($type);

        $resource->setOriginalFileName($originalFilename)
            ->setFileName($file->getFilename());

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
     * @throws IOException
     */
    public function removeResourceFile(ResourceInterface $resource)
    {
        $fs = new Filesystem;
        try {
            $fs->remove($this->getUploadedFilePath($resource));
        } catch (UnexpectedValueException $e) {
            $this->logger->warning("Missing file {$resource->getFileName()} ({$resource->getOriginalFileName()})");
        }
    }

    /**
     * Get the url of a "Resource" (for the web), only works if inside the web root directory
     *
     * @param ResourceInterface $resource
     * @return string
     * @throws UnexpectedValueException
     */
    public function getUploadedFileUrl(ResourceInterface $resource)
    {
        // @todo use routing and custom controller
    }

    /**
     * Get the path for an uploaded file, does not check if file exists
     *
     * @throws UnexpectedValueException
     * @param ResourceInterface $resource
     * @return string
     */
    public function getUploadedFilePath(ResourceInterface $resource)
    {
        // @todo do it with Oneup ?
        $directory = $this->getFileUploadBasePath($resource->getType());
        return $directory . '/' . $resource->getFileName();
    }

    /**
     * Get the base directory for a type of file upload configuration
     *
     * @param string $type
     * @return string
     * @throws UnexpectedValueException
     */
    public function getFileUploadBasePath($type)
    {
        $config = $this->getResourceType($type);
        $directory = $config->getUploadConfig()['storage']['directory'];
        if (!$directory) {
//            var_dump($config->getUploadConfig());
            throw new UnexpectedValueException("You must set the directory directive in the oneup bundle for type {$type}");
        }
        return rtrim($directory, '/');
    }

    public function getResourceType($type)
    {
        if (!isset($this->resourceConfigurations[$type])) {
            throw new UnexpectedValueException("Unknown resource type '{$type}'");
        }
        return $this->resourceConfigurations[$type];
    }
    /**
     * @param $type
     * @return ResourceInterface
     */
    protected function createByType($type)
    {
        $entity = $this->getResourceType($type)->getEntity();
        return new $entity();
    }

    /**
     * @param $code
     * @param array $resourceConfiguration
     */
    public function addResourceConfiguration($code, array $resourceConfiguration)
    {
        $object = new ResourceTypeConfiguration($code, $resourceConfiguration['entity'], $resourceConfiguration['upload_config']);
        $this->resourceConfigurations[$code] = $object;
    }


    /**
     * Load inheritance mapping automatically if using Resource entity from this bundle
     *
     * @param LoadClassMetadataEventArgs $event
     * @throws MappingException
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        if (!$metadata instanceof ClassMetadataInfo) {
            return;
        }
        $class = $metadata->getReflectionClass();

        if ($class === null) {
            $class = new \ReflectionClass($metadata->getName());
        }

        if ($class->getName() !== self::BASE_RESOURCE) {
            return;
        }

        foreach ($this->resourceConfigurations as $resourceConfiguration) {
            if (is_a($resourceConfiguration->getEntity(), self::BASE_RESOURCE, true)) {
                $metadata->addDiscriminatorMapClass(
                    $resourceConfiguration->getCode(),
                    $resourceConfiguration->getEntity()
                );
            }
        }
    }
}
