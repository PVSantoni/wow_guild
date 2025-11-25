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

    #[ORM\Column(nullable: true)]
    private ?int $level = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    #[ORM\ManyToOne(targetEntity: CharacterClass::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CharacterClass $class = null;

    #[ORM\ManyToOne(targetEntity: Specialization::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Specialization $activeSpec = null;

    // CORRECTION ICI : Les attributs sont maintenant au bon endroit
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

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

    public function getClass(): ?CharacterClass
    {
        return $this->class;
    }

    public function setClass(?CharacterClass $class): static
    {
        $this->class = $class;
        return $this;
    }

    public function getActiveSpec(): ?Specialization
    {
        return $this->activeSpec;
    }

    public function setActiveSpec(?Specialization $activeSpec): static
    {
        $this->activeSpec = $activeSpec;
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
