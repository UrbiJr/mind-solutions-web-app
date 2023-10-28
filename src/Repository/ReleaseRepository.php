<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Release;

class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Release::class);
    }

    public function getById(int $id): Release
    {
        $release = $this->find($id);

        return $release;
    }

    /**
     * @return Release[]
     */
    public function getAll(): array
    {
        return $this->findAll();
    }

    public function update(Release $release): void
    {
        $em = $this->getEntityManager();
        $em->persist($release);
        $em->flush();
    }
}
