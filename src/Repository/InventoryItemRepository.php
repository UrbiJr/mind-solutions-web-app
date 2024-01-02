<?php

namespace App\Repository;

use App\Entity\InventoryItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    public function getById(int $id): ?InventoryItem
    {
        return $this->find($id);
    }

    /**
     * @return InventoryItem[]
     */
    public function getAllByUserId(int $userId): array
    {
        return $this->findBy(['user' => $userId]);
    }

    /**
     * @return InventoryItem[]
     */
    public function getSalesByUserId(int $userId): array
    {
        $sales = $this->findBy([
            'user' => $userId,
            'status' => InventoryItem::ITEM_SOLD
        ]);

        return $sales;
    }

    /**
     * Removes from arrayA elements of arrayB that have $id in common
     * (skip comparing other attributes).
     * 
     * @return InventoryItem[]
     */
    function arrayDiff(array $arrayA, array $arrayB)
    {
        $filteredArray = array_filter($arrayA, function ($itemA) use ($arrayB) {
            $idA = $itemA->getId();
            foreach ($arrayB as $itemB) {
                $idB = $itemB->getId();
                if ($idA === $idB) {
                    return false; // Exclude element from arrayA
                }
            }
            return true; // Include element in arrayA
        });

        return array_values($filteredArray); // Re-index the array
    }

    function add(InventoryItem $inventoryItem, User $user)
    {
        $inventoryItem->setUser($user);
        $this->getEntityManager()->persist($inventoryItem);
        $this->getEntityManager()->flush();
    }

    function edit(InventoryItem $inventoryItem)
    {
        $this->getEntityManager()->persist($inventoryItem);
        $this->getEntityManager()->flush();
    }

    /**
     * Updates multiple fields on multiple items
     *
     * @param  array  $itemIds The items to be updated
     * @param  array  $attributes The attribute name => attribute value array of attributes to update
     * @param  Integer  $userId The user whose inventory belongs to
     * @throws \Exception if an error occurs during the update
     * @return array array of updated items as map of updated attributes
     */
    function massEdit(array $itemIds, array $attributes, User $user)
    {
        try {
            $updates = [];

            // Iterate over the document IDs and update each one
            foreach ($itemIds as $id) {
                $inventoryItem = InventoryItem::fromDataArray($attributes, $user);
                $inventoryItem->setId($id);

                $this->getEntityManager()->persist($inventoryItem);
                $updates[$id] = $inventoryItem;
            }

            $this->getEntityManager()->flush();
            return $updates;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function delete($itemId)
    {
        $inventoryItem = $this->find($itemId);
        if ($inventoryItem) {
            $this->getEntityManager()->remove($inventoryItem);
            $this->flush();
        }
    }

    function massDelete(array $itemIds): int
    {
        $deleted = 0;

        try {
            // Iterate over the document IDs and update each one
            foreach ($itemIds as $id) {
                $item = $this->find($id);
                if ($item) {
                    $this->getEntityManager()->remove($item);
                    $deleted++;
                }
            }

            $this->getEntityManager()->flush();
            return $deleted;
        } catch (\Exception $e) {
            throw $e;
        }

        return $deleted;
    }
}
