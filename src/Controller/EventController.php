<?php

namespace App\Controller;

// Importation des dépendances nécessaires
use App\Entity\Event;
use App\Form\EventType;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EventRepository;
use App\Service\NotificationService;
use App\Form\EventSearchType;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Booking;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventController extends AbstractController
{
    // Injection du service de notification
    public function __construct(private NotificationService $notificationService) {}

    // Liste des événements (avec recherche et pagination)
    #[Route('/events', name: 'event_list')]
    public function list(
        Request $request,
        EventRepository $eventRepository,
        PaginatorInterface $paginator
    ): Response {
        // Formulaire de recherche d'événements
        $form = $this->createForm(EventSearchType::class, null, [
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
        $form->handleRequest($request);

        // Récupération des critères + construction de la requête DQL
        $criteria = $form->getData();
        $query = $eventRepository->searchQueryBuilder($criteria ?? []);

        // Pagination
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6 // Événements par page
        );

        return $this->render('event/index.html.twig', [
            'searchForm' => $form->createView(),
            'pagination' => $pagination,
        ]);
    }

    // Création d’un événement par un organisateur
    #[Route('/event/create', name: 'event_create')]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // Accès réservé aux organisateurs ou administrateurs
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER', null, 'Accès refusé : seuls les organisateurs ou administrateurs peuvent créer un événement.');

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Initialisation des champs système
            $event->setCreatedAt(new \DateTimeImmutable());
            $event->setCreatedBy($this->getUser());

            // Génération du slug à partir du titre
            $slug = $slugger->slug($event->getTitle())->lower();
            $event->setSlug($slug);

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('event_create');
        }

        return $this->render('event/create.html.twig', [
            'eventForm' => $form->createView(),
        ]);
    }

    // Édition d’un événement existant
    #[Route('/event/edit/{id}', name: 'event_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        $event = $em->getRepository(Event::class)->find($id);

        // Sécurité : l'utilisateur doit être le créateur ou admin
        if ((!$event || $event->getCreatedBy() !== $this->getUser()) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres événements.');
        }

        // Événements déjà démarrés non modifiables (hors admin)
        if ($event->getStartAt() <= new \DateTimeImmutable() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Impossible de modifier un événement déjà démarré.');
            return $this->redirectToRoute('my_events');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Événement mis à jour avec succès.');
            return $this->redirectToRoute('my_events');
        }

        return $this->render('event/edit.html.twig', [
            'eventForm' => $form->createView(),
            'event' => $event,
        ]);
    }

    // Affichage détaillé d’un événement
    #[Route('/event/{slug}', name: 'event_show')]
    public function show(string $slug, Event $event, EntityManagerInterface $em): Response
    {
        // Recherche de l’événement via le slug
        $event = $em->getRepository(Event::class)->findOneBy(['slug' => $slug]);

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    // Suppression d’un événement
    #[Route('/event/delete/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        $event = $em->getRepository(Event::class)->find($id);

        // Vérifie que l’utilisateur est bien le créateur (ou admin)
        if ((!$event || $event->getCreatedBy() !== $this->getUser()) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Suppression non autorisée.');
        }

        // Événements passés non supprimables (hors admin)
        if ($event->getStartAt() <= new \DateTimeImmutable() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Impossible de supprimer un événement déjà démarré.');
            return $this->redirectToRoute('my_events');
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('my_events');
        }

        $em->remove($event);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès.');
        return $this->redirectToRoute('my_events');
    }

    // Réservation pour un événement
    #[Route('/event/{id}/book', name: 'event_book')]
    public function book(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $now = new \DateTimeImmutable();

        // Bloque la réservation si l'événement est déjà démarré
        if ($event->getStartAt() <= $now) {
            $this->addFlash('danger', 'Les réservations sont closes pour cet événement (déjà démarré).');
            return $this->redirectToRoute('event_list');
        }

        // Bloque si plus aucune place
        if ($event->getRemainingSeats() <= 0) {
            $this->addFlash('danger', 'Cet événement est complet.');
            return $this->redirectToRoute('event_list');
        }

        // Création du formulaire de réservation
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking, [
            'max_quantity' => $event->getRemainingSeats()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrement de la réservation
            $booking->setEvent($event);
            $booking->setUser($this->getUser());
            $booking->setReservedAt(new \DateTimeImmutable());

            $em->persist($booking);
            $em->flush();

            // Notification à l’utilisateur
            $this->notificationService->sendReservation($booking);

            $this->addFlash('success', 'Réservation effectuée avec succès.');
            return $this->redirectToRoute('event_list');
        }

        return $this->render('booking/book.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    // Affichage des événements créés par l’utilisateur connecté
    #[Route('/myevents', name: 'my_events')]
    public function myEvents(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        $user = $this->getUser();

        // Recherche des événements de l’utilisateur
        $events = $eventRepository->findBy(['createdBy' => $user]);

        return $this->render('event/my_events.html.twig', [
            'events' => $events,
        ]);
    }

    // Liste des participants d’un événement
    #[Route('/event/{slug}/participants', name: 'event_participants')]
    public function participants(Event $event, string $slug, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        // Recherche de l’événement via slug
        $event = $em->getRepository(Event::class)->findOneBy(['slug' => $slug]);

        $user = $this->getUser();

        // L’organisateur ne peut consulter que ses propres événements (hors admin)
        if ($event->getCreatedBy() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez consulter que vos propres événements.');
        }

        // Récupère toutes les réservations liées à cet événement
        $bookings = $event->getBookings();

        return $this->render('event/participants.html.twig', [
            'event' => $event,
            'bookings' => $bookings,
        ]);
    }
}
