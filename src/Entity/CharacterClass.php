<?php

namespace App\Entity;

use App\Repository\CharacterClassRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacterClassRepository::class)]
class CharacterClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $apiKey = null;

    /**
     * @var Collection<int, Specialization>
     */
    #[ORM\OneToMany(targetEntity: Specialization::class, mappedBy: 'characterClass', orphanRemoval: true)]
    private Collection $specializations;

    public function __construct()
    {
        $this->specializations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return Collection<int, Specialization>
     */
    public function getSpecializations(): Collection
    {
        return $this->specializations;
    }

    public function addSpecialization(Specialization $specialization): static
    {
        if (!$this->specializations->contains($specialization)) {
            $this->specializations->add($specialization);
            $specialization->setCharacterClass($this);
        }
        return $this;
    }

    public function removeSpecialization(Specialization $specialization): static
    {
        if ($this->specializations->removeElement($specialization)) {
            if ($specialization->getCharacterClass() === $this) {
                $specialization->setCharacterClass(null);
            }
        }
        return $this;
    }
}
