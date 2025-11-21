<?php

namespace App\Entity;

use App\Repository\BisListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BisListRepository::class)]
class BisList
{
    // =========================================================================
    // SECTION 1 : CONSTANTES POUR LES CHOIX DES FORMULAIRES
    // On centralise ici toutes les valeurs possibles pour les classes et spés
    // =========================================================================

    // Noms des classes tels que retournés par l'API Blizzard (name_en)
    public const CLASS_DEATH_KNIGHT = 'DEATH KNIGHT';
    public const CLASS_DRUID = 'DRUID';
    public const CLASS_CHASSEUR = 'CHASSEUR';
    public const CLASS_MAGE = 'MAGE';
    public const CLASS_MONK = 'MONK';
    public const CLASS_PALADIN = 'PALADIN';
    public const CLASS_PRIEST = 'PRIEST';
    public const CLASS_ROGUE = 'ROGUE';
    public const CLASS_SHAMAN = 'SHAMAN';
    public const CLASS_WARLOCK = 'WARLOCK';
    public const CLASS_WARRIOR = 'WARRIOR';

    // Tableau formaté pour le champ ChoiceType de Symfony (Classe)
    public const CLASSES_CHOICES = [
        'Chevalier de la Mort' => self::CLASS_DEATH_KNIGHT,
        'Druide' => self::CLASS_DRUID,
        'Chasseur' => self::CLASS_CHASSEUR,
        'Mage' => self::CLASS_MAGE,
        'Moine' => self::CLASS_MONK,
        'Paladin' => self::CLASS_PALADIN,
        'Prêtre' => self::CLASS_PRIEST,
        'Voleur' => self::CLASS_ROGUE,
        'Chaman' => self::CLASS_SHAMAN,
        'Démoniste' => self::CLASS_WARLOCK,
        'Guerrier' => self::CLASS_WARRIOR,
    ];

    // Noms des spés tels que retournés par l'API Blizzard (name)
    // NOTE : Tu devras compléter cette liste avec les spés des autres classes
    public const SPEC_FROST = 'Givre';
    public const SPEC_FIRE = 'Feu';
    public const SPEC_ARCANE = 'Arcanes';
    public const SPEC_ASSASSINATION = 'Assassinat';
    public const SPEC_COMBAT = 'Combat';
    public const SPEC_SUBTLETY = 'Finesse';
    public const SPEC_SURVIVAL = 'Survie';
    // ... etc.

    // Tableau formaté pour le champ ChoiceType de Symfony (Spécialisation)
    public const SPECS_CHOICES = [
        'Givre (Mage)' => self::SPEC_FROST,
        'Feu (Mage)' => self::SPEC_FIRE,
        'Arcanes (Mage)' => self::SPEC_ARCANE,
        'Assassinat (Voleur)' => self::SPEC_ASSASSINATION,
        'Combat (Voleur)' => self::SPEC_COMBAT,
        'Finesse (Voleur)' => self::SPEC_SUBTLETY,
        'Survie (Chasseur)' => self::SPEC_SURVIVAL,
        // ... etc.
    ];

    // =========================================================================
    // SECTION 2 : PROPRIÉTÉS DE L'ENTITÉ (ce qui est en BDD)
    // =========================================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $characterClass = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialization = null;

    /**
     * @var Collection<int, BisItem>
     */
    #[ORM\OneToMany(targetEntity: BisItem::class, mappedBy: 'bisList', cascade: ['persist', 'remove'])]
    private Collection $bisItems;

    public function __construct()
    {
        $this->bisItems = new ArrayCollection();
    }

    // =========================================================================
    // SECTION 3 : GETTERS ET SETTERS
    // =========================================================================

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

    public function getCharacterClass(): ?string
    {
        return $this->characterClass;
    }

    public function setCharacterClass(string $characterClass): static
    {
        $this->characterClass = $characterClass;
        return $this;
    }

    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(?string $specialization): static
    {
        $this->specialization = $specialization;
        return $this;
    }

    /**
     * @return Collection<int, BisItem>
     */
    public function getBisItems(): Collection
    {
        return $this->bisItems;
    }

    public function addBisItem(BisItem $bisItem): static
    {
        if (!$this->bisItems->contains($bisItem)) {
            $this->bisItems->add($bisItem);
            $bisItem->setBisList($this);
        }
        return $this;
    }

    public function removeBisItem(BisItem $bisItem): static
    {
        if ($this->bisItems->removeElement($bisItem)) {
            // set the owning side to null (unless already changed)
            if ($bisItem->getBisList() === $this) {
                $bisItem->setBisList(null);
            }
        }
        return $this;
    }
}
