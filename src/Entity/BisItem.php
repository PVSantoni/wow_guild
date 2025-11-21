<?php

namespace App\Entity;

use App\Repository\BisItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BisItemRepository::class)]
class BisItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slot = null;

    #[ORM\Column]
    private ?int $itemId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $itemName = null;

    #[ORM\ManyToOne(inversedBy: 'bisItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BisList $bisList = null;

    public ?array $apiDetails = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlot(): ?string
    {
        return $this->slot;
    }

    public function setSlot(?string $slot): static
    {
        $this->slot = $slot;

        return $this;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): static
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getItemName(): ?string
    {
        return $this->itemName;
    }

    public function setItemName(?string $itemName): static
    {
        $this->itemName = $itemName;

        return $this;
    }

public function getBisList(): ?BisList
{
    return $this->bisList;
}

public function setBisList(?BisList $bisList): static
{
    $this->bisList = $bisList;

    return $this;
}
}
