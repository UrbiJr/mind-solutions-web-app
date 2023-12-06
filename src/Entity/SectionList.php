<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\InventoryItem;
use App\Repository\SectionListRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * EventSection
 */
#[ORM\Table(name: 'SectionLists')]
#[ORM\UniqueConstraint(name: 'unique_event_id', columns: ['event_id'])]
#[ORM\Entity(repositoryClass: SectionListRepository::class)]
#[UniqueEntity(fields: ['event_id'], message: 'There is already an event with this section list')]
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
     * @var string
     */
    #[ORM\Column(name: 'event_id', type: 'string', nullable: false)]
    private $eventId;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'sections', type: 'json', nullable: true)]
    private $sections;



    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of sections
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Set the value of sections
     *
     * @return  self
     */
    public function setSections($sections)
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * Get the value of eventId
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Set the value of eventId
     *
     * @return  self
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;

        return $this;
    }
}
