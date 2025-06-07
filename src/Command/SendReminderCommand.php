<?php

namespace App\Command;

// Importation des classes nécessaires
use App\Repository\EventRepository;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'reminder:send',
    description: 'Envoie des rappels d\'événements aux utilisateurs inscrits.',
)]
class SendReminderCommand extends Command
{
    private EventRepository $eventRepository;
    private NotificationService $notificationService;

    // Injection des dépendances via le constructeur
    public function __construct(EventRepository $eventRepository, NotificationService $notificationService)
    {
        parent::__construct();
        $this->eventRepository = $eventRepository;
        $this->notificationService = $notificationService;
    }

    // Méthode exécutée lors de l'appel de la commande
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Cible tous les événements qui commencent dans 1 jour
        $targetDate = (new \DateTimeImmutable())->modify('+1 day');

        // Récupération des événements dont la date de début est exactement demain
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('DATE(e.startAt) = :date')
            ->setParameter('date', $targetDate->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        // Si aucun événement trouvé, affiche un message et arrête l'exécution
        if (empty($events)) {
            $output->writeln('Aucun événement pour demain.');
            return Command::SUCCESS;
        }

        // Parcourt les événements trouvés
        foreach ($events as $event) {
            // Pour chaque réservation liée à l'événement, envoie un rappel
            foreach ($event->getBookings() as $booking) {
                $user = $booking->getUser();

                // Utilisation du service de notification pour envoyer un rappel personnalisé
                $this->notificationService->sendEventReminder($user, $event);
            }
        }

        // Confirmation de l'envoi des rappels
        $output->writeln('Rappels envoyés avec succès.');
        return Command::SUCCESS;
    }
}
