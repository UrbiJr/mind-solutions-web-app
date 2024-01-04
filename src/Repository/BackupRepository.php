<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Backup;

class BackupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Backup::class);
    }

    function delete($backupId)
    {
        $backup = $this->find($backupId);
        if ($backup) {
            $this->getEntityManager()->remove($backup);
            $this->getEntityManager()->flush();
        }
    }

    function add(Backup $release)
    {
        $this->getEntityManager()->persist($release);
        $this->getEntityManager()->flush();
    }
}
