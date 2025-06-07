<?php

namespace App\DataFixtures;

// Importation des classes nécessaires
use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventFixtures extends Fixture implements DependentFixtureInterface
{
    // Le service Symfony pour générer des slugs à partir de chaînes de caractères
    private SluggerInterface $slugger;

    // Injection du slugger via le constructeur
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    // Méthode principale pour charger les données en base
    public function load(ObjectManager $manager): void
    {
        // Instanciation de Faker en français
        $faker = Factory::create('fr_FR');

        // Génère 10 événements
        for ($i = 1; $i <= 10; $i++) {
            $event = new Event();

            // Titre et description de l'événement
            $event->setTitle($faker->sentence(3));
            $event->setDescription($faker->paragraph(2));

            // Date de début entre 2 et 30 jours dans le futur
            $event->setStartAt(new \DateTimeImmutable(
                $faker->dateTimeBetween('+2 days', '+30 days')->format('Y-m-d H:i:s')
            ));

            // Capacité aléatoire entre 20 et 100 personnes
            $event->setCapacity($faker->numberBetween(20, 100));

            // Association à un lieu existant (référence posée dans LocationFixtures)
            $event->setLocation($this->getReference(
                'loc_' . $faker->numberBetween(0, 3),
                \App\Entity\Location::class
            ));

            // Créateur de l'événement (référence posée dans UserFixtures)
            $event->setCreatedBy($this->getReference(
                'organizer_' . $faker->numberBetween(1, 3),
                \App\Entity\User::class
            ));

            // Type de l'événement (référence posée dans EventTypeFixtures)
            $event->setType($this->getReference(
                'type_' . $faker->numberBetween(0, 3),
                \App\Entity\EventType::class
            ));

            // Génération du slug à partir du titre
            $slug = $this->slugger->slug($event->getTitle())->lower();
            $event->setSlug($slug);

            // Enregistrement de l’événement
            $manager->persist($event);

            // Stockage de la référence pour d'autres fixtures (ex: BookingFixtures)
            $this->addReference("event_$i", $event);
        }

        // Sauvegarde en base
        $manager->flush();
    }

    // Déclaration des dépendances vers d'autres fixtures
    public function getDependencies(): array
    {
        return [
            \App\DataFixtures\LocationFixtures::class,
            \App\DataFixtures\EventTypeFixtures::class,
            \App\DataFixtures\UserFixtures::class,
        ];
    }
}
