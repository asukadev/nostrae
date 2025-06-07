<?php

namespace App\DataFixtures;

// Importation de la classe de base pour les fixtures
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    // Méthode appelée automatiquement lors de l’exécution des fixtures
    public function load(ObjectManager $manager): void
    {
        // Exemple de code pour ajouter une entité en base (commenté ici)
        // $product = new Product();
        // $manager->persist($product);

        // Exécute les insertions en base de données
        $manager->flush();
    }
}
