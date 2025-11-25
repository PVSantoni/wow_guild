<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface; // <-- AJOUTER CE USE
use Doctrine\Persistence\ObjectManager;

// On implémente l'interface pour les groupes
class CategorieFixtures extends Fixture implements FixtureGroupInterface
{
    // On ajoute cette méthode pour définir le groupe
    public static function getGroups(): array
    {
        return ['categories']; // Le nom du groupe est 'categories'
    }

    public function load(ObjectManager $manager): void
    {
        // ... le reste de votre code ne change pas
        $categories = ['10 NM', '10 HM', '25 NM', '25 HM'];
        foreach ($categories as $categorieNom) {
            $categorie = new Categorie();
            $categorie->setNom($categorieNom);
            $manager->persist($categorie);
        }
        $manager->flush();
    }
}
