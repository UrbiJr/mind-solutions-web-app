<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\SectionList;

class SectionListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SectionList::class);
    }

    public function getById(int $id): SectionList
    {
        $sectionList = $this->find($id);

        return $sectionList;
    }

    /**
     * @return SectionList[]
     */
    public function getAll(): array
    {
        return $this->findAll();
    }

    public function getByEventId(string $eventId): SectionList
    {
        return $this->findOneBy(['event_id' => $eventId]);
    } 
}
