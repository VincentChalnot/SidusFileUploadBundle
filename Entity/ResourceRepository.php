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
     * Find all "fileNames" in
     *
     * @return Collection
     */
    public function getFileNames()
    {
        $qb = $this
            ->createQueryBuilder('r')
            ->select('r.fileName')
        ;

        $fileNames = new ArrayCollection();
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $fileNames[$item['fileName']] = $item['fileName'];
        }

        return $fileNames;
    }
}
