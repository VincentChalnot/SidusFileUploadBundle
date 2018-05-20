<?php

namespace Sidus\FileUploadBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;

/**
 * Delete resource files on entity removal
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceSubscriber implements EventSubscriber
{
    /** @var ResourceManagerInterface */
    protected $resourceManager;

    /**
     * @param ResourceManagerInterface $resourceManager
     */
    public function __construct(ResourceManagerInterface $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'preRemove',
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws \UnexpectedValueException
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof ResourceInterface) {
            $this->resourceManager->removeResourceFile($entity);

            return;
        }
    }
}
