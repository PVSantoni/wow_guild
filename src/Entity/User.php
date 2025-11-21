<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $pseudo = null;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'user')]
    private Collection $inscriptions;

    // =========================================================================
    // MODIFICATIONS APPLIQUÉES ICI
    // =========================================================================

    // 1. SUPPRESSION des anciennes propriétés de personnage
    // private ?string $characterName = null;
    // private ?string $characterRealmSlug = null;
    // private ?string $characterRegion = null;

    /**
     * @var Collection<int, Character> La liste de tous les personnages de cet utilisateur
     */
    // 2. CORRECTION de 'mappedBy' pour correspondre à la propriété '$user' de l'entité Character
    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $characters;

    /**
     * @var Character|null Le personnage actuellement sélectionné par l'utilisateur pour être affiché
     */
    // 3. AJOUT de la nouvelle propriété pour le personnage actif
    #[ORM\OneToOne(targetEntity: Character::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
private ?Character $activeCharacter = null;


    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
        $this->characters = new ArrayCollection();
    }

    // ... (les getters/setters pour id, email, roles, password, pseudo restent les mêmes)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void {}

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setUser($this);
        }
        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getUser() === $this) {
                $inscription->setUser(null);
            }
        }
        return $this;
    }

    // =========================================================================
    // SUPPRESSION des anciens getters et setters de personnage
    // =========================================================================

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            // 4. CORRECTION pour utiliser le bon setter
            $character->setUser($this);
        }
        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        if ($this->characters->removeElement($character)) {
            if ($character->getUser() === $this) {
                // 4. CORRECTION pour utiliser le bon setter
                $character->setUser(null);
            }
        }
        return $this;
    }

    // =========================================================================
    // AJOUT des getters et setters pour le personnage actif
    // =========================================================================

    public function getActiveCharacter(): ?Character
    {
        return $this->activeCharacter;
    }

    public function setActiveCharacter(?Character $activeCharacter): static
    {
        $this->activeCharacter = $activeCharacter;
        return $this;
    }
}
