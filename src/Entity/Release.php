<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * Release
 */
#[ORM\Table(name: 'Releases')]
#[ORM\Index(name: 'create_from_fk', columns: ['author'])]
#[ORM\Entity]
class Release
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'country_code', type: 'string', length: 4, nullable: true)]
    private $countryCode;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'city', type: 'string', length: 256, nullable: true)]
    private $city;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'location', type: 'string', length: 256, nullable: true)]
    private $location;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'description', type: 'string', length: 256, nullable: true)]
    private $description;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'event_date', type: 'datetime', nullable: true)]
    private $eventDate;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'release_date', type: 'datetime', nullable: true)]
    private $releaseDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'retailer', type: 'string', length: 256, nullable: true)]
    private $retailer;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'early_link', type: 'string', length: 256, nullable: true)]
    private $earlyLink;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'comments', type: 'string', length: 512, nullable: true)]
    private $comments;

    /**
     * @var \User
     */
    #[ORM\JoinColumn(name: 'author', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'User')]
    private $author;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getRetailer(): ?string
    {
        return $this->retailer;
    }

    public function setRetailer(?string $retailer): static
    {
        $this->retailer = $retailer;

        return $this;
    }

    public function getEarlyLink(): ?string
    {
        return $this->earlyLink;
    }

    public function setEarlyLink(?string $earlyLink): static
    {
        $this->earlyLink = $earlyLink;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function __clone()
    {
        $this->id = null;
    }
}