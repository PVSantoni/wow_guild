<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use App\Entity\Specialization;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
class Inscription
{
    // --> AJOUT 1 : La liste des rôles de raid possibles
    public const ROLES = [
        'Tank',
        'Soigneur',
        'DPS'
    ];

    const STATUTS = ['Confirmé', 'Incertain', 'En attente', 'Absent'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    // --> AJOUT 2 : La nouvelle propriété pour le rôle joué
    #[ORM\Column(length: 50, nullable: true)] // On la met nullable au début pour ne pas causer d'erreurs avec les anciennes inscriptions
    private ?string $playedRole = null;

    #[ORM\ManyToOne] // Une inscription a une seule spécialisation
    #[ORM\JoinColumn(nullable: true)] // On la rend nullable pour les inscriptions "Incertain"
    private ?Specialization $specialization = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    // --> AJOUT 3 : Les nouveaux getter et setter
    public function getPlayedRole(): ?string
    {
        return $this->playedRole;
    }

    public function setPlayedRole(?string $playedRole): static
    {
        $this->playedRole = $playedRole;
        return $this;
    }

    public function getSpecialization(): ?Specialization
    {
        return $this->specialization;
    }

    public function setSpecialization(?Specialization $specialization): static
    {
        $this->specialization = $specialization;
        return $this;
    }
}
