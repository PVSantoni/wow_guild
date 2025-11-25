# WOW Guild — Documentation du projet

Ce dépôt contient une application Symfony destinée à gérer une guilde World of Warcraft : événements, inscriptions, listes BiS, profils de personnages et intégration Battle.net.

**Résumé rapide**: application Symfony (PHP) avec authentification, gestion d'événements (raid), inscriptions par rôle/spécialisation, gestion de listes BiS (Best in Slot), tableau de bord administrateur et intégration Battle.net pour récupérer les informations d'armurerie.

**Table des matières**

-   **Fonctionnalités**
-   **Plan du site / Routes principales**
-   **Entités et relations**
-   **Controllers / Services / Repositories**
-   **Templates & Forms**
-   **Installation & Exécution rapide**

**Fonctionnalités**

-   **Authentification** : inscription, login, logout, gestion des rôles (`ROLE_USER`, `ROLE_ADMIN`).
-   **Profils utilisateurs** : association de plusieurs personnages, sélection d'un personnage actif, affichage de l'armurerie via l'API Battle.net.
-   **Gestion d'événements** : création/édition/suppression d'événements (admin), affichage d'un calendrier/public list.
-   **Inscriptions aux événements** : inscription par statut (Confirmé / En attente / Incertain / Absent), choix de rôle (Tank / Soigneur / DPS) et spécialisation.
-   **Roster & management** : interface admin pour gérer le roster d'un événement (confirmation, mise en attente, suppression d'inscriptions), promotion/démotion d'utilisateurs, mise à jour du rang guilde.
-   **Listes BiS** : création/édition/suppression de listes BiS (import depuis JSON wowsims), association d'items et affichage comparé à l'équipement du personnage.
-   **Intégration Battle.net** : récupération du profil, médias, équipement et informations d'items (avec cache et token OAuth).

**Plan du site / Routes principales**

-   `GET /` — page d'accueil / calendrier hebdomadaire (`app_home`).
-   `GET /evenements` — liste des événements (`app_evenement_index`).
-   `GET /evenements/categorie/{id}` — filtrage par catégorie (`app_evenement_filter_category`).
-   `GET|POST /evenement/new` — créer un événement (admin) (`app_evenement_new`).
-   `GET /evenement/{id}` — détail événement (`app_evenement_show`).
-   `POST /evenement/{id}/inscription` — s'inscrire / modifier son inscription (`app_evenement_inscription`).
-   `GET|POST /evenement/{id}/edit` — modifier un événement (admin) (`app_evenement_edit`).
-   `POST /evenement/{id}` — supprimer un événement (admin) (`app_evenement_delete`).
-   `POST /inscription/{id}/update-status` — changer statut d'inscription (admin) (`app_inscription_update_status`).
-   `POST /inscription/{id}/remove` — supprimer inscription (admin) (`app_inscription_remove`).
-   `GET /roster` — page roster de la guilde (`app_guild_roster`).
-   `GET /profil` — profil connecté et armurerie (`app_profile_index`).
-   `GET /profil/{id}` — profil public d'un membre (`app_public_profile`).
-   `GET|POST /character/*` — CRUD personnages (préfixe `/character`, utilisateurs connectés).
-   `GET|POST /admin/*` — dashboard admin, gestion utilisateurs, événements, listes BiS (`AdminController`, `BisListController`).
-   `GET /login`, `GET /logout`, `GET|POST /register` — sécurité & enregistrement.

**Entités et liaisons**

-   `User`

    -   Champs importants: `id`, `email`, `password`, `pseudo`, `guildRank`, `roles`.
    -   Relations: OneToMany `inscriptions` (Inscription), OneToMany `characters` (Character), OneToOne `activeCharacter` (Character).

-   `Character`

    -   Champs: `id`, `characterName`, `characterRealmSlug`, `characterRegion`.
    -   Relations: ManyToOne `user` (User).

-   `Evenement`

    -   Champs: `id`, `nom`, `description`, `dateDebut`, `nbPlacesMax`, `tanksRequis`, `soigneursRequis`, `dpsRequis`.
    -   Relations: OneToMany `inscriptions` (Inscription), ManyToOne `categorie` (Categorie).

-   `Inscription`

    -   Champs: `id`, `statut` (Confirmé/Incertain/En attente/Absent), `playedRole` (Tank/Soigneur/DPS).
    -   Relations: ManyToOne `user` (User), ManyToOne `evenement` (Evenement), ManyToOne `specialization` (Specialization).

-   `Categorie`

    -   Champs: `id`, `nom`.
    -   Relations: OneToMany `evenements` (Evenement).

-   `BisList`

    -   Champs: `id`, `name`, `characterClass`, `specialization`.
    -   Relations: OneToMany `bisItems` (BisItem).

-   `BisItem`

    -   Champs: `id`, `slot`, `itemId`, `itemName`, `apiDetails` (stocke données API en runtime).
    -   Relations: ManyToOne `bisList` (BisList).

-   `CharacterClass`

    -   Champs: `id`, `name`, `apiKey`.
    -   Relations: OneToMany `specializations` (Specialization).

-   `Specialization`
    -   Champs: `id`, `name`.
    -   Relations: ManyToOne `characterClass` (CharacterClass).

Relations clés (résumé):

-   `User` 1 — \* `Character`
-   `User` 1 — \* `Inscription`
-   `Character` \* — 1 `User`
-   `Evenement` 1 — \* `Inscription`
-   `Evenement` \* — 1 `Categorie`
-   `Inscription` \* — 1 `Specialization`
-   `CharacterClass` 1 — \* `Specialization`
-   `BisList` 1 — \* `BisItem`

**Controllers (fichiers & rôle)**

-   `HomeController` (`src/Controller/HomeController.php`) : page d'accueil, calendrier hebdomadaire des événements.
-   `EvenementController` (`src/Controller/EvenementController.php`) : listing, filtre par catégorie, CRUD événements (admin), gestion des inscriptions (inscription, désinscription, statuts) et opérations admin sur inscriptions.
-   `BisListController` (`src/Controller/BisListController.php`) : CRUD listes BiS (préfixe `/admin/bislist`), import JSON depuis wowsims et parsing des items.
-   `CharacterController` (`src/Controller/CharacterController.php`) : gestion des personnages d'un utilisateur (ajout, édition, suppression, définir actif).
-   `ProfileController` (`src/Controller/ProfileController.php`) : affichage du profil utilisateur/public, intégration Battle.net, match entre équipement et listes BiS, résolution des items/slots.
-   `GuildController` (`src/Controller/GuildController.php`) : affichage du roster (liste membres).
-   `AdminController` (`src/Controller/AdminController.php`) : tableau de bord admin, gestion utilisateurs (promotion/démotion/rang), liste globale d'événements, gestion roster admin.
-   `SecurityController` (`src/Controller/SecurityController.php`) : login/logout.
-   `RegistrationController` (`src/Controller/RegistrationController.php`) : page d'inscription d'un nouvel utilisateur.

**Services**

-   `BattleNetApiService` (`src/Service/BattleNetApiService.php`)
    -   Gestion du token OAuth (cache) pour Blizzard/Battle.net.
    -   Méthodes principales: `getCharacterProfile`, `getCharacterMedia`, `getCharacterEquipment`, `getItemInfo`, `getItemMediaUrl`.
    -   Utilise `HttpClientInterface` + `CacheInterface` pour limiter les appels API et stocker les résultats.

**Repositories & méthodes importantes**

-   `InscriptionRepository` (`src/Repository/InscriptionRepository.php`)
    -   Méthode notable: `countConfirmedByRole(Evenement, string $role)` — compte les inscriptions confirmées par rôle (utilisé lors d'une inscription pour vérifier places disponibles).
-   `UserRepository` (`src/Repository/UserRepository.php`) : `upgradePassword` pour ré-hasher/mettre à jour le mot de passe.
-   `BisListRepository`, `EvenementRepository`, `CategorieRepository`, `CharacterRepository`, etc. : repositories standards générés par Symfony/Doctrine (possibilité d'ajouter des méthodes spécifiques si besoin).

**Templates & Forms**

-   Templates clés dans `templates/`: `base.html.twig`, `home/index.html.twig`, `evenement/*` (`index`, `show`, `new`, `edit`, `_form`, `_delete_form`), `profile/*`, `bis_list/*`, `character/*`, `admin/*`, `registration/register.html.twig`, `security/login.html.twig`.
-   Forms: `EvenementType`, `CharacterType`, `BisListType`, `RegistrationFormType` (présents dans `src/Form/`).

**Installation & Exécution rapide**

1. Installer les dépendances PHP (depuis la racine du projet) :

```powershell
composer install
```

2. Installer les dépendances JS / compil assets si nécessaire :

```powershell
npm install
npm run dev
```

3. Configurer `.env` / variables d'environnement (ex : `DATABASE_URL`, `BATTLE_NET_CLIENT_ID`, `BATTLE_NET_CLIENT_SECRET`).

4. Créer la base de données & exécuter les migrations :

```powershell
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Lancer le serveur de développement Symfony :

```powershell
symfony server:start
```

6. Accéder à l'application via `http://localhost:8000`.

**Remarques / points d'attention**

-   L'intégration Battle.net utilise des endpoints et namespaces `profile-classic-<region>` et `static-<region>` — vérifier la région (paramétrable via service) selon l'environnement (classic vs retail).
-   Le parsing JSON des listes BiS attend une structure fournie par wowsims (`player.equipment.items`).
-   Les rôles d'inscriptions sont limités à `Inscription::ROLES` (Tank/Soigneur/DPS) et les statuts à `Inscription::STATUTS`.
-   Vérifier les autorisations (`IsGranted('ROLE_ADMIN')` ou `ROLE_USER`) sur les routes admin et profil/character.

---

Si vous voulez, je peux :

-   générer un diagramme ER (PNG/SVG) montrant les entités et leurs relations ;
-   ajouter des README plus détaillés par dossier (`src/`, `templates/`, `config/`), ou
-   commiter le `README.md` et ouvrir une MR avec les changements.

Indiquez ce que vous préférez comme prochaine action.
