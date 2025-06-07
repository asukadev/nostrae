<?php

namespace App\Controller;

// Importation des classes nécessaires
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Entity\EventType as EntityEventType;
use App\Entity\OrganizerRequest;
use App\Repository\OrganizerRequestRepository;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use App\Form\EventTypeType;
use App\Repository\EventTypeRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class AdminController extends AbstractController
{
    // Tableau de bord administrateur
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        EventRepository $eventRepository,
        BookingRepository $bookingRepository,
        EventTypeRepository $eventTypeRepository,
        LocationRepository $locationRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupération des statistiques globales
        $totalUsers = $userRepository->count([]);
        $totalOrganizers = $userRepository->countByRole('ROLE_ORGANIZER');
        $totalEvents = $eventRepository->count([]);
        $totalBookings = $bookingRepository->count([]);
        $completedEvents = $eventRepository->countCompletedEvents();
        $nextEvent = $eventRepository->findNextEvent();
        $eventsByType = $eventTypeRepository->countEventsByType();
        $topCities = $locationRepository->topCities();
        $averageFillingRate = $eventRepository->averageFillingRate();
        $types = $eventRepository->countByType();
        $eventTypeLabels = array_column($types, 'type');
        $eventTypeData = array_column($types, 'count');
        $upcomingEvents = $eventRepository->findUpcomingEvents(); // ex: dans 30 jours
        $eventLabels = array_map(fn($e) => $e['title'], $upcomingEvents);
        $eventDates = array_map(fn($e) => $e['startAt']->format('Y-m-d H:i:s'), $upcomingEvents);
        $bookings = $bookingRepository->countByEventTitle();
        $bookingLabels = array_column($bookings, 'title');
        $bookingData = array_column($bookings, 'count');

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalOrganizers' => $totalOrganizers,
            'totalEvents' => $totalEvents,
            'totalBookings' => $totalBookings,
            'completedEvents' => $completedEvents,
            'nextEvent' => $nextEvent,
            'eventsByType' => $eventsByType,
            'topCityLabels' => array_column($topCities, 'city'),
            'topCityData' => array_column($topCities, 'eventCount'),
            'averageFillingRate' => $averageFillingRate,
            'eventTypeLabels' => $eventTypeLabels,
            'eventTypeData' => $eventTypeData,
            'bookingLabels' => $bookingLabels,
            'bookingData' => $bookingData,
            'eventTimelineLabels' => $eventLabels,
            'eventTimelineDates' => $eventDates,
        ]);
    }

    // Liste paginée des utilisateurs
    #[Route('/admin/users', name: 'admin_users')]
    public function users(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Requête pour récupérer tous les utilisateurs
        $query = $em->createQuery('SELECT u FROM App\Entity\User u ORDER BY u.id DESC');

        // Pagination des résultats
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Page actuelle (défaut = 1)
            10 // Nombre d'utilisateurs par page
        );

        return $this->render('admin/users.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // Modifier un utilisateur
    #[Route('/admin/user/edit/{id}', name: 'admin_edit_user')]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Création du formulaire d'édition de l'utilisateur
        $form = $this->createFormBuilder($user)
            ->add('username')
            ->add('email')
            ->add('roles', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Organisateur' => 'ROLE_ORGANIZER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Rôles',
            ])
            ->getForm();

        $form->handleRequest($request);

        // Sauvegarde en base si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    // Supprimer un utilisateur
    #[Route('/admin/user/delete/{id}', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users');
        }

        // Vérification du token CSRF avant suppression
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_users');
    }

    // Liste paginée des événements
    #[Route('/admin/events', name: 'admin_events')]
    public function events(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Requête pour tous les événements triés par date
        $query = $em->createQuery('SELECT e FROM App\Entity\Event e ORDER BY e.startAt DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/events.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // Modifier un événement
    #[Route('/admin/event/edit/{id}', name: 'admin_edit_event')]
    public function editEvent(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Événement mis à jour avec succès.');
            return $this->redirectToRoute('admin_events');
        }

        return $this->render('admin/edit_event.html.twig', [
            'eventForm' => $form->createView(),
            'event' => $event,
        ]);
    }

    // Supprimer un événement
    #[Route('/admin/event/delete/{id}', name: 'admin_delete_event', methods: ['POST'])]
    public function deleteEvent(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_events');
    }

    // Liste des demandes d'organisateur
    #[Route('/admin/organizer-requests', name: 'admin_organizer_requests')]
    public function organizerRequests(OrganizerRequestRepository $requestRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $requests = $requestRepo->findBy([], ['requestedAt' => 'DESC']);

        return $this->render('admin/organizer_requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    // Accepter une demande d'organisateur
    #[Route('/admin/organizer-request/accept/{id}', name: 'admin_accept_organizer', methods: ['POST'])]
    public function acceptOrganizerRequest(OrganizerRequest $organizerRequest, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('accept_organizer_' . $organizerRequest->getId(), $request->request->get('_token'))) {
            $user = $organizerRequest->getUser();
            $roles = $user->getRoles();
            $roles[] = 'ROLE_ORGANIZER';
            $user->setRoles(array_unique($roles));

            $em->remove($organizerRequest);
            $em->flush();

            $this->addFlash('success', 'Demande acceptée ! L\'utilisateur est désormais Organisateur.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_organizer_requests');
    }

    // Refuser une demande d'organisateur
    #[Route('/admin/organizer-request/refuse/{id}', name: 'admin_refuse_organizer', methods: ['POST'])]
    public function refuseOrganizerRequest(OrganizerRequest $organizerRequest, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('refuse_organizer_' . $organizerRequest->getId(), $request->request->get('_token'))) {
            $em->remove($organizerRequest);
            $em->flush();
            $this->addFlash('success', 'Demande refusée et supprimée.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_organizer_requests');
    }

    // Liste des réservations
    #[Route('/admin/bookings', name: 'admin_bookings')]
    public function bookings(Request $request, BookingRepository $bookingRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $query = $bookingRepository->createQueryBuilder('b')
            ->orderBy('b.reservedAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/bookings.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // Supprimer une réservation
    #[Route('/admin/booking/delete/{id}', name: 'admin_delete_booking', methods: ['POST'])]
    public function deleteBooking(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete_booking_' . $booking->getId(), $request->request->get('_token'))) {
            $em->remove($booking);
            $em->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_bookings');
    }

    // Liste des lieux
    #[Route('/admin/locations', name: 'admin_locations')]
    public function locations(Request $request, LocationRepository $locationRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $query = $locationRepository->createQueryBuilder('l')
            ->orderBy('l.name', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/locations.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // Créer un lieu
    #[Route('/admin/location/create', name: 'admin_create_location')]
    public function createLocation(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($location);
            $em->flush();
            $this->addFlash('success', 'Lieu créé avec succès.');
            return $this->redirectToRoute('admin_locations');
        }

        return $this->render('admin/edit_location.html.twig', [
            'locationForm' => $form->createView(),
            'title' => 'Créer un Lieu'
        ]);
    }

    // Modifier un lieu
    #[Route('/admin/location/edit/{id}', name: 'admin_edit_location')]
    public function editLocation(Location $location, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Lieu mis à jour.');
            return $this->redirectToRoute('admin_locations');
        }

        return $this->render('admin/edit_location.html.twig', [
            'locationForm' => $form->createView(),
            'title' => 'Modifier un Lieu'
        ]);
    }

    // Supprimer un lieu
    #[Route('/admin/location/delete/{id}', name: 'admin_delete_location', methods: ['POST'])]
    public function deleteLocation(Location $location, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete_location_' . $location->getId(), $request->request->get('_token'))) {
            $em->remove($location);
            $em->flush();
            $this->addFlash('success', 'Lieu supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_locations');
    }

    // Liste des types d'événements
    #[Route('/admin/event-types', name: 'admin_event_types')]
    public function eventTypes(Request $request, EventTypeRepository $eventTypeRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $query = $eventTypeRepository->createQueryBuilder('et')
            ->orderBy('et.name', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/event_types.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // Créer un type d'événement
    #[Route('/admin/event-type/create', name: 'admin_create_event_type')]
    public function createEventType(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $eventTypeType = new EntityEventType();
        $form = $this->createForm(EventTypeType::class, $eventTypeType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($eventTypeType);
            $em->flush();
            $this->addFlash('success', 'Type d\'événement créé avec succès.');
            return $this->redirectToRoute('admin_event_types');
        }

        return $this->render('admin/event_type_form.html.twig', [
            'eventTypeTypeForm' => $form->createView(),
            'title' => 'Créer un Type d\'Événement'
        ]);
    }

    // Modifier un type d'événement
    #[Route('/admin/event-type/edit/{id}', name: 'admin_edit_event_type')]
    public function editEventType(EntityEventType $eventTypeType, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(EventTypeType::class, $eventTypeType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Type d\'événement mis à jour.');
            return $this->redirectToRoute('admin_event_types');
        }

        return $this->render('admin/event_type_form.html.twig', [
            'eventTypeTypeForm' => $form->createView(),
            'title' => 'Modifier un Type d\'Événement'
        ]);
    }

    // Supprimer un type d'événement
    #[Route('/admin/event-type/delete/{id}', name: 'admin_delete_event_type', methods: ['POST'])]
    public function deleteEventType(EntityEventType $eventType, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete_event_type_' . $eventType->getId(), $request->request->get('_token'))) {
            $em->remove($eventType);
            $em->flush();
            $this->addFlash('success', 'Type d\'événement supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_event_types');
    }
}
