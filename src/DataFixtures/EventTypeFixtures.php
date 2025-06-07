<?php

namespace App\DataFixtures;

// Importation des classes nécessaires
use App\Entity\EventType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventTypeFixtures extends Fixture
{
    // Méthode appelée pour insérer les types d'événements en base de données
    public function load(ObjectManager $manager): void
    {
        // Liste prédéfinie de types d'événements
        $types = ['Concert', 'Théâtre', 'Exposition', 'Conférence'];

        // Pour chaque nom de type, on crée une entité EventType correspondante
        foreach ($types as $i => $name) {
            $type = new EventType();

            // Définit le nom du type (ex: Concert)
            $type->setName($name);

            // Définit le champ "location" à la même valeur
            // (à adapter si le champ a une autre sémantique dans l'entité)
            $type->setLocation($name);

            // Préparation de l'insertion en base
            $manager->persist($type);

            // Enregistre une référence nommée pour pouvoir la réutiliser dans d'autres fixtures
            $this->addReference("type_$i", $type);
        }

        // Exécute l'insertion de tous les types
        $manager->flush();
    }
}
