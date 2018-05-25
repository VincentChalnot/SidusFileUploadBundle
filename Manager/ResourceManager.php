<?php

namespace Sidus\FileUploadBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Emgag\Flysystem\Hash\HashPlugin;
use League\Flysystem\File;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Metadata\MetadataUpdaterInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Sidus\FileUploadBundle\Registry\FilesystemRegistry;
use Symfony\Component\Routing\RouterInterface;
use UnexpectedValueException;

/**
 * Manage access to resources: entities and files
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceManager implements ResourceManagerInterface
{
    /** @var ResourceTypeConfiguration[] */
    protected $resourceConfigurations;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var LoggerInterface */
    protected $logger;

    /** @var FilesystemRegistry */
    protected $filesystemRegistry;

    /** @var RouterInterface */
    protected $router;

    /** @var MetadataUpdaterInterface|null */
    protected $metadataUpdater;

    /**
     * @param ManagerRegistry               $doctrine
     * @param LoggerInterface               $logger
     * @param FilesystemRegistry            $filesystemRegistry
     * @param RouterInterface               $router
     * @param MetadataUpdaterInterface|null $metadataUpdater
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        FilesystemRegistry $filesystemRegistry,
        RouterInterface $router,
        MetadataUpdaterInterface $metadataUpdater = null
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->filesystemRegistry = $filesystemRegistry;
        $this->router = $router;
        $this->metadataUpdater = $metadataUpdater;
    }

    /**
     * {@inheritdoc}
     */
    public function addFile(File $file, $originalFilename, $type = null)
    {
        $fs = $this->getFilesystemForType($type);
        $fs->addPlugin(new HashPlugin());

        /** @noinspection PhpUndefinedMethodInspection */
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

        if ($this->metadataUpdater) {
            $this->metadataUpdater->updateResourceMetadata($resource, $file);
        }

        $className = ClassUtils::getClass($resource);
        $entityManager = $this->doctrine->getManagerForClass($className);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException("No manager found for class {$className}");
        }
        $entityManager->persist($resource);
        $entityManager->flush();

        return $resource;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getFilesystem(ResourceInterface $resource)
    {
        return $this->getFilesystemForType($resource->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesystemForType($type)
    {
        $config = $this->getResourceTypeConfiguration($type);

        return $this->filesystemRegistry->getFilesystem($config->getFilesystemKey());
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getResourceConfigurations()
    {
        return $this->resourceConfigurations;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceTypeConfiguration($type)
    {
        if (!isset($this->resourceConfigurations[$type])) {
            throw new UnexpectedValueException("Unknown resource type '{$type}'");
        }

        return $this->resourceConfigurations[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function addResourceConfiguration($code, array $resourceConfiguration)
    {
        $object = new ResourceTypeConfiguration($code, $resourceConfiguration);
        $this->resourceConfigurations[$code] = $object;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function getRepositoryForType($type)
    {
        $class = $this->getResourceTypeConfiguration($type)->getEntity();

        $entityManager = $this->doctrine->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException("No manager found for class {$class}");
        }

        return $entityManager->getRepository($class);
    }

    /**
     * @param string $type
     *
     * @return ResourceInterface
     */
    protected function createByType($type)
    {
        $entity = $this->getResourceTypeConfiguration($type)->getEntity();

        return new $entity();
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
