<?php

namespace App\Repository;

use App\Entity\InventoryValue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryValue::class);
    }

    public function getById(int $id): ?InventoryValue
    {
        return $this->find($id);
    }

    function add(InventoryValue $inventoryValue, User $user)
    {
        $inventoryValue->setUser($user);
        $this->getEntityManager()->persist($inventoryValue);
        $this->getEntityManager()->flush();
    }

    /**
     * @return InventoryValue[]
     */
    public function getAllByUserId(int $userId): array
    {
        return $this->findBy(['user' => $userId]);
    }
}
