<?php

namespace App\Repository;

use App\Entity\CaptchaProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CaptchaProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaptchaProvider::class);
    }

    public function getById(int $id): ?CaptchaProvider
    {
        return $this->find($id);
    }
}
