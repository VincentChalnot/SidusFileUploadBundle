<?php

namespace Sidus\FileUploadBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oneup\UploaderBundle\Uploader\File\GaufretteFile;
use Psr\Log\LoggerInterface;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Component\Routing\RouterInterface;
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

    /** @var FilesystemMap */
    protected $filesystemMap;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param Registry $doctrine
     * @param LoggerInterface $logger
     * @param FilesystemMap $filesystemMap
     * @param RouterInterface $router
     */
    public function __construct(Registry $doctrine, LoggerInterface $logger, FilesystemMap $filesystemMap, RouterInterface $router)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->filesystemMap = $filesystemMap;
        $this->router = $router;
    }

    /**
     * Add an entry for Resource entity in database at each upload
     *
     * @param GaufretteFile $file
     * @param string $originalFilename
     * @param string $type
     * @return ResourceInterface
     * @throws \InvalidArgumentException|UnexpectedValueException
     */
    public function addFile(GaufretteFile $file, $originalFilename, $type = null)
    {
        $resource = $this->createByType($type)
            ->setOriginalFileName($originalFilename)
            ->setFileName($file->getKey());

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
     * @throws UnexpectedValueException
     */
    public function removeResourceFile(ResourceInterface $resource)
    {
        $fs = $this->getFilesystem($resource);
        try {
            $fs->delete($resource->getFileName());
        } catch (\RuntimeException $e) {
            $this->logger->warning("Tried to remove missing file {$resource->getFileName()} ({$resource->getOriginalFileName()})");
        }
    }

    /**
     * Get the url of a "Resource" (for the web)
     *
     * @param ResourceInterface $resource
     * @param string $action
     * @param bool $absolute
     * @return string
     * @throws \Exception
     */
    public function getFileUrl(ResourceInterface $resource, $action = 'download', $absolute = false)
    {
        /** @noinspection Symfony2PhpRouteMissingInspection */
        return $this->router->generate("sidus_file_upload.file.{$action}", [
            'type' => $resource->getType(),
            'filename' => $resource->getFileName(),
        ], $absolute);
    }

    /**
     * @param ResourceInterface $resource
     * @return Filesystem
     * @throws UnexpectedValueException
     */
    public function getFilesystem(ResourceInterface $resource)
    {
        return $this->getFilesystemForType($resource->getType());
    }

    /**
     * @param $type
     * @return Filesystem
     * @throws UnexpectedValueException
     */
    public function getFilesystemForType($type)
    {
        $config = $this->getResourceTypeConfiguration($type);
        return $this->filesystemMap->get($config->getFilesystemKey());
    }

    /**
     * Get the path for an uploaded file, does not check if file exists
     *
     * @param ResourceInterface $resource
     * @return GaufretteFile
     * @throws FileNotFound|UnexpectedValueException
     */
    public function getFile(ResourceInterface $resource)
    {
        $fs = $this->getFilesystem($resource);
        if (!$fs->has($resource->getFileName())) {
            return false;
        }
        $file = $fs->get($resource->getFileName());
        return new GaufretteFile($file, $fs); // @todo Where do I get getStreamWrapperPrefix if needed ?
    }

    /**
     * @param $type
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
     * @param $type
     * @return ResourceInterface
     * @throws UnexpectedValueException
     */
    protected function createByType($type)
    {
        $entity = $this->getResourceTypeConfiguration($type)->getEntity();
        return new $entity();
    }

    /**
     * @param $code
     * @param array $resourceConfiguration
     */
    public function addResourceConfiguration($code, array $resourceConfiguration)
    {
        $object = new ResourceTypeConfiguration($code, $resourceConfiguration);
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
