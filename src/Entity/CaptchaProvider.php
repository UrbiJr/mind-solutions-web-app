<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CaptchaProvider
 */
#[ORM\Table(name: 'CaptchaProviders')]
#[ORM\Entity]
class CaptchaProvider
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
    #[ORM\Column(name: 'name', type: 'string', length: 64, nullable: true)]
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }


}
