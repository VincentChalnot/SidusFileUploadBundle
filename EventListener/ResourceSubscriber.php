<?php

namespace Sidus\FileUploadBundle\EventListener;

use Sidus\FileUploadBundle\Manager\ResourceManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Sidus\FileUploadBundle\Model\ResourceInterface;

class ResourceSubscriber implements EventSubscriber
{

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    public function getSubscribedEvents()
    {
        return [
            'preRemove',
        ];
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof ResourceInterface) {
            $this->resourceManager->removeResourceFile($entity);
            return;
        }
    }
}
