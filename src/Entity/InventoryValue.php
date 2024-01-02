<?php

namespace App\Entity;

use App\Repository\InventoryValueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * InventoryValue
 */
#[ORM\Table(name: 'InventoryValues')]
#[ORM\Index(name: 'fk_user_id', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: InventoryValueRepository::class)]
class InventoryValue
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'User')]
    private $user;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name', type: 'string', length: 64, nullable: true)]
    private $currency;

    /**
     * @var \DateTimeInterface|DateTime|null
     */
    #[ORM\Column(name: 'timestamp', type: 'datetime', nullable: true)]
    private $timestamp;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'value', type: 'integer', nullable: true)]
    private $value;

    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * Get the value of currency
     */ 
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @return  self
     */ 
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of timestamp
     */ 
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp
     *
     * @return  self
     */ 
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of value
     */ 
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of value
     *
     * @return  self
     */ 
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of user
     */ 
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */ 
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
