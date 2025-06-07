<?php

namespace App\DataFixtures;

// Importation des classes nécessaires
use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    // Méthode exécutée automatiquement lors du chargement des fixtures
    public function load(ObjectManager $manager): void
    {
        // Données statiques représentant des lieux réels ou fictifs
        $locations = [
            ['Théâtre National', 'Paris', '1 avenue des Arts', '75001'],
            ['Palais des Congrès', 'Marseille', '123 rue du Port', '13008'],
            ['Salle des Fêtes', 'Lyon', '5 place des Fêtes', '69003'],
            ['La Cigale', 'Paris', '120 bd Rochechouart', '75018'],
        ];

        // Boucle sur chaque jeu de données pour créer une entité Location correspondante
        foreach ($locations as $i => [$name, $city, $address, $postalCode]) {
            $location = new Location();

            // Initialisation des propriétés de l'entité
            $location->setName($name);
            $location->setCity($city);
            $location->setAddress($address);
            $location->setPostalCode($postalCode); // Champ spécifique à l'entité

            // Préparation à l'insertion
            $manager->persist($location);

            // Enregistrement d'une référence utilisable par d'autres fixtures (ex: EventFixtures)
            $this->addReference("loc_$i", $location);
        }

        // Enregistrement des entités en base
        $manager->flush();
    }
}
