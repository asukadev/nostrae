<?php

namespace App\Controller;

// Importation des classes nécessaires
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\NotificationService;
use Dompdf\Dompdf;

final class BookingController extends AbstractController
{
    // Injection du service de notification via le constructeur
    public function __construct(private NotificationService $notificationService) {}

    // Page d'accueil de la section réservation
    #[Route('/booking', name: 'app_booking')]
    public function index(): Response
    {
        return $this->render('booking/index.html.twig', [
            'controller_name' => 'BookingController',
        ]);
    }

    // Afficher les réservations de l'utilisateur connecté
    #[Route('/my-bookings', name: 'my_bookings')]
    public function myBookings(EntityManagerInterface $em): Response
    {
        $user = $this->getUser(); // Utilisateur actuellement connecté

        // Récupération des réservations liées à cet utilisateur
        $bookings = $em->getRepository(Booking::class)->findBy(['user' => $user]);

        return $this->render('booking/my_bookings.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/book/print/{id}', name: "booking_print")]
    public function print(Booking $booking, Request $request, EntityManagerInterface $em)
    {
        $html =  $this->renderView('components/_ticket.html.twig', [
            "user" => $this->getUser(),
            "booking" => $booking,
            "event" => $booking->getEvent(),
        ]);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
         
        return new Response (
            $dompdf->stream('resume', ["Attachment" => false]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/pdf']
        );
    }

    // Annuler une réservation existante
    #[Route('/booking/cancel/{id}', name: 'booking_cancel', methods: ['POST'])]
    public function cancel(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser(); // Utilisateur actuel

        // Vérification des permissions : seul le propriétaire ou un admin peut annuler
        if ($booking->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette réservation.');
        }

        // Vérification du jeton CSRF
        if (!$this->isCsrfTokenValid('cancel_booking_' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('my_bookings');
        }

        // Suppression de la réservation
        $em->remove($booking);
        $em->flush();

        // Envoi d'une notification d'annulation
        $this->notificationService->sendCancellation($booking);

        // Confirmation à l'utilisateur
        $this->addFlash('success', 'Réservation annulée avec succès.');

        return $this->redirectToRoute('my_bookings');
    }
}
