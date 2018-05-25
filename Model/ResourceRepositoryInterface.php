<?php

namespace Sidus\FileUploadBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;

/**
 * Common method that needs to be declared for resource repositories
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ResourceRepositoryInterface extends ObjectRepository
{
    /**
     * Find all "paths" for a given resource
     *
     * @return array
     */
    public function getPaths();
}
