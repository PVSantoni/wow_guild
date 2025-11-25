<?php
// src/DataFixtures/ClassAndSpecFixtures.php

namespace App\DataFixtures;

use App\Entity\CharacterClass;
use App\Entity\Specialization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ClassAndSpecFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['classes_specs'];
    }

    public const CLASSES_DATA = [
        'Guerrier' => ['apiKey' => 'WARRIOR', 'specs' => ['Armes', 'Fureur', 'Protection']],
        'Paladin' => ['apiKey' => 'PALADIN', 'specs' => ['Sacré', 'Protection', 'Vindicte']],
        'Chasseur' => ['apiKey' => 'HUNTER', 'specs' => ['Maîtrise des bêtes', 'Précision', 'Survie']],
        'Voleur' => ['apiKey' => 'ROGUE', 'specs' => ['Assassinat', 'Combat', 'Finesse']],
        'Prêtre' => ['apiKey' => 'PRIEST', 'specs' => ['Discipline', 'Sacré', 'Ombre']],
        'Chevalier de la mort' => ['apiKey' => 'DEATH_KNIGHT', 'specs' => ['Sang', 'Givre', 'Impie']],
        'Chaman' => ['apiKey' => 'SHAMAN', 'specs' => ['Élémentaire', 'Amélioration', 'Restauration (Chaman)']],
        'Moine' => ['apiKey' => 'MONK', 'specs' => ['Maître brasseur', 'Marche-vent', 'Tisse-brume']],
        'Mage' => ['apiKey' => 'MAGE', 'specs' => ['Arcane', 'Feu', 'Givre']],
        'Démoniste' => ['apiKey' => 'WARLOCK', 'specs' => ['Affliction', 'Démonologie', 'Destruction']],
        'Druide' => ['apiKey' => 'DRUID', 'specs' => ['Équilibre', 'Combat farouche', 'Gardien', 'Restauration (Druide)']],
    ];

    public function load(ObjectManager $manager): void
    {
        // ==========================================================
        // LA LOGIQUE COMPLÈTE EST RESTAURÉE ICI
        // ==========================================================
        foreach (self::CLASSES_DATA as $className => $data) {
            // 1. On crée l'entité Classe
            $classEntity = new CharacterClass();
            $classEntity->setName($className);
            $classEntity->setApiKey($data['apiKey']);
            $manager->persist($classEntity);

            // 2. Pour chaque classe, on crée ses spés
            foreach ($data['specs'] as $specName) {
                $specEntity = new Specialization();
                // Si le nom de la spé contient déjà une parenthèse, on l'utilise tel quel
                if (str_contains($specName, '(')) {
                    $specEntity->setName($specName);
                } else {
                    // Sinon, on construit le nom "Spécialisation (Classe)"
                    $specEntity->setName("$specName ($className)");
                }
                // On lie la spé à sa classe parente
                $specEntity->setCharacterClass($classEntity);
                $manager->persist($specEntity);
            }
        }

        // On exécute la sauvegarde de tout en une seule fois
        $manager->flush();
    }
}
