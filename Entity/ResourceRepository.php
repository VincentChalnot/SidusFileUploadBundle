<?php

namespace Sidus\FileUploadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Sidus\FileUploadBundle\Model\ResourceRepositoryInterface;

/**
 * Basic implementation of resource repository
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceRepository extends EntityRepository implements ResourceRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        $qb = $this
            ->createQueryBuilder('r')
            ->select('r.path');

        $paths = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $paths[$item['path']] = $item['path'];
        }

        return $paths;
    }
}
