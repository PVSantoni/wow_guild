<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $characterName = null;

    #[ORM\Column(length: 255)]
    private ?string $characterRealmSlug = null;

    #[ORM\Column(length: 255)]
    private ?string $characterRegion = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }

    public function setCharacterName(string $characterName): static
    {
        $this->characterName = $characterName;
        return $this;
    }

    public function getCharacterRealmSlug(): ?string
    {
        return $this->characterRealmSlug;
    }

    public function setCharacterRealmSlug(string $characterRealmSlug): static
    {
        $this->characterRealmSlug = $characterRealmSlug;
        return $this;
    }

    public function getCharacterRegion(): ?string
    {
        return $this->characterRegion;
    }

    public function setCharacterRegion(string $characterRegion): static
    {
        $this->characterRegion = $characterRegion;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
