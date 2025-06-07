<?php

namespace App\DataFixtures;

// Importation des classes nécessaires
use App\Entity\Booking;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    // Charge les données de réservation en base
    public function load(ObjectManager $manager): void
    {
        // Création d’une instance de Faker pour générer des données aléatoires en français
        $faker = Factory::create('fr_FR');

        // Création de 20 réservations fictives
        for ($i = 0; $i < 20; $i++) {
            $booking = new Booking();

            // Association d’un événement existant via une référence définie dans EventFixtures
            $booking->setEvent($this->getReference(
                'event_' . $faker->numberBetween(1, 10),
                \App\Entity\Event::class
            ));

            // Association d’un utilisateur existant via une référence définie dans UserFixtures
            $booking->setUser($this->getReference(
                'user_' . $faker->numberBetween(1, 5),
                \App\Entity\User::class
            ));

            // Définition d’une quantité de billets entre 1 et 3
            $booking->setQuantity($faker->numberBetween(1, 3));

            // Date de réservation générée aléatoirement dans les 5 derniers jours
            $booking->setReservedAt(new \DateTimeImmutable(
                $faker->dateTimeBetween('-5 days', 'now')->format('Y-m-d H:i:s')
            ));

            // Préparation de l’insertion
            $manager->persist($booking);
        }

        // Exécution de toutes les insertions en base
        $manager->flush();
    }

    // Déclare que BookingFixtures dépend d’EventFixtures et UserFixtures
    public function getDependencies(): array
    {
        return [
            \App\DataFixtures\EventFixtures::class,
            \App\DataFixtures\UserFixtures::class,
        ];
    }
}
