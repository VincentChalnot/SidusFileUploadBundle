<?php

namespace Sidus\FileUploadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * Currently not used
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceRepository extends EntityRepository
{
    /**
     * Find all "paths" in
     *
     * @return Collection
     */
    public function getPaths()
    {
        $qb = $this
            ->createQueryBuilder('r')
            ->select('r.path');

        $paths = new ArrayCollection();
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $paths[$item['path']] = $item['path'];
        }

        return $paths;
    }
}
