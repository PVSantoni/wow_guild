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
    // SECTION 1 : CONSTANTES DES CLASSES (Format API Anglais)
    // =========================================================================

    public const CLASS_DEATH_KNIGHT = 'DEATH_KNIGHT';
    public const CLASS_DRUID        = 'DRUID';
    public const CLASS_HUNTER       = 'HUNTER';
    public const CLASS_MAGE         = 'MAGE';
    public const CLASS_MONK         = 'MONK';
    public const CLASS_PALADIN      = 'PALADIN';
    public const CLASS_PRIEST       = 'PRIEST';
    public const CLASS_ROGUE        = 'ROGUE';
    public const CLASS_SHAMAN       = 'SHAMAN';
    public const CLASS_WARLOCK      = 'WARLOCK';
    public const CLASS_WARRIOR      = 'WARRIOR';

    // Choix pour le formulaire (Label FR => Value EN)
    public const CLASSES_CHOICES = [
        'Chevalier de la Mort' => self::CLASS_DEATH_KNIGHT,
        'Druide'             => self::CLASS_DRUID,
        'Chasseur'           => self::CLASS_HUNTER,
        'Mage'               => self::CLASS_MAGE,
        'Moine'              => self::CLASS_MONK,
        'Paladin'            => self::CLASS_PALADIN,
        'Prêtre'             => self::CLASS_PRIEST,
        'Voleur'             => self::CLASS_ROGUE,
        'Chaman'             => self::CLASS_SHAMAN,
        'Démoniste'          => self::CLASS_WARLOCK,
        'Guerrier'           => self::CLASS_WARRIOR,
    ];

    // =========================================================================
    // SECTION 2 : CONSTANTES DES SPÉCIALISATIONS (Format API Anglais)
    // =========================================================================

    // DEATH KNIGHT
    public const SPEC_DK_BLOOD  = 'Blood';
    public const SPEC_DK_FROST  = 'Frost';
    public const SPEC_DK_UNHOLY = 'Unholy';

    // DRUID
    public const SPEC_DRUID_BALANCE  = 'Balance';
    public const SPEC_DRUID_FERAL    = 'Feral';
    public const SPEC_DRUID_GUARDIAN = 'Guardian';
    public const SPEC_DRUID_RESTO    = 'Restoration';

    // HUNTER
    public const SPEC_HUNTER_BM   = 'Beast Mastery';
    public const SPEC_HUNTER_MM   = 'Marksmanship';
    public const SPEC_HUNTER_SURV = 'Survival';

    // MAGE
    public const SPEC_MAGE_ARCANE = 'Arcane';
    public const SPEC_MAGE_FIRE   = 'Fire';
    public const SPEC_MAGE_FROST  = 'Frost';

    // MONK
    public const SPEC_MONK_BREW     = 'Brewmaster';
    public const SPEC_MONK_MIST     = 'Mistweaver';
    public const SPEC_MONK_WIND     = 'Windwalker';

    // PALADIN
    public const SPEC_PALADIN_HOLY = 'Holy';
    public const SPEC_PALADIN_PROT = 'Protection';
    public const SPEC_PALADIN_RET  = 'Retribution';

    // PRIEST
    public const SPEC_PRIEST_DISC   = 'Discipline';
    public const SPEC_PRIEST_HOLY   = 'Holy';
    public const SPEC_PRIEST_SHADOW = 'Shadow';

    // ROGUE
    public const SPEC_ROGUE_ASSA   = 'Assassination';
    public const SPEC_ROGUE_COMBAT = 'Combat';
    public const SPEC_ROGUE_SUB    = 'Subtlety';

    // SHAMAN
    public const SPEC_SHAMAN_ELE   = 'Elemental';
    public const SPEC_SHAMAN_ENH   = 'Enhancement';
    public const SPEC_SHAMAN_RESTO = 'Restoration';

    // WARLOCK
    public const SPEC_WARLOCK_AFF    = 'Affliction';
    public const SPEC_WARLOCK_DEMO   = 'Demonology';
    public const SPEC_WARLOCK_DESTRO = 'Destruction';

    // WARRIOR
    public const SPEC_WARRIOR_ARMS = 'Arms';
    public const SPEC_WARRIOR_FURY = 'Fury';
    public const SPEC_WARRIOR_PROT = 'Protection';


    // Choix pour le formulaire (Label FR => Value EN)
    public const SPECS_CHOICES = [
        // DK
        'Sang (DK)'            => self::SPEC_DK_BLOOD,
        'Givre (DK)'           => self::SPEC_DK_FROST,
        'Impie (DK)'           => self::SPEC_DK_UNHOLY,
        // Druide
        'Équilibre (Druide)'   => self::SPEC_DRUID_BALANCE,
        'Farouche (Druide)'    => self::SPEC_DRUID_FERAL,
        'Gardien (Druide)'     => self::SPEC_DRUID_GUARDIAN,
        'Restauration (Druide)' => self::SPEC_DRUID_RESTO,
        // Chasseur
        'Maîtrise des bêtes (Chasseur)' => self::SPEC_HUNTER_BM,
        'Précision (Chasseur)'          => self::SPEC_HUNTER_MM,
        'Survie (Chasseur)'             => self::SPEC_HUNTER_SURV,
        // Mage
        'Arcanes (Mage)' => self::SPEC_MAGE_ARCANE,
        'Feu (Mage)'     => self::SPEC_MAGE_FIRE,
        'Givre (Mage)'   => self::SPEC_MAGE_FROST,
        // Moine
        'Maître brasseur (Moine)' => self::SPEC_MONK_BREW,
        'Tisse-brume (Moine)'     => self::SPEC_MONK_MIST,
        'Marche-vent (Moine)'     => self::SPEC_MONK_WIND,
        // Paladin
        'Sacré (Paladin)'      => self::SPEC_PALADIN_HOLY,
        'Protection (Paladin)' => self::SPEC_PALADIN_PROT,
        'Vindicte (Paladin)'   => self::SPEC_PALADIN_RET,
        // Prêtre
        'Discipline (Prêtre)' => self::SPEC_PRIEST_DISC,
        'Sacré (Prêtre)'      => self::SPEC_PRIEST_HOLY,
        'Ombre (Prêtre)'      => self::SPEC_PRIEST_SHADOW,
        // Voleur
        'Assassinat (Voleur)' => self::SPEC_ROGUE_ASSA,
        'Combat (Voleur)'     => self::SPEC_ROGUE_COMBAT,
        'Finesse (Voleur)'    => self::SPEC_ROGUE_SUB,
        // Chaman
        'Élémentaire (Chaman)'    => self::SPEC_SHAMAN_ELE,
        'Amélioration (Chaman)'   => self::SPEC_SHAMAN_ENH,
        'Restauration (Chaman)'   => self::SPEC_SHAMAN_RESTO,
        // Démoniste
        'Affliction (Démoniste)'  => self::SPEC_WARLOCK_AFF,
        'Démonologie (Démoniste)' => self::SPEC_WARLOCK_DEMO,
        'Destruction (Démoniste)' => self::SPEC_WARLOCK_DESTRO,
        // Guerrier
        'Armes (Guerrier)'      => self::SPEC_WARRIOR_ARMS,
        'Fureur (Guerrier)'     => self::SPEC_WARRIOR_FURY,
        'Protection (Guerrier)' => self::SPEC_WARRIOR_PROT,
    ];

    // =========================================================================
    // SECTION 3 : PROPRIÉTÉS
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
    // SECTION 4 : GETTERS ET SETTERS
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
            if ($bisItem->getBisList() === $this) {
                $bisItem->setBisList(null);
            }
        }

        return $this;
    }
}
