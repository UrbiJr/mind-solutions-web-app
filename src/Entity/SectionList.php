<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\InventoryItem;

/**
 * EventSection
 */
#[ORM\Table(name: 'EventSections')]
#[ORM\Index(name: 'fk_item_id', columns: ['item_id'])]
#[ORM\Entity(repositoryClass: EventSectionRepository::class)]
class SectionList
{

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \InventoryItem
     */
    #[ORM\JoinColumn(name: 'item', referencedColumnName: 'id')]
    #[ORM\OneToOne(targetEntity: 'User')]
    private $inventoryItem;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'sections', type: 'json', nullable: true)]
    private $sections;
}
