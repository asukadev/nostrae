<?php

namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\OrganizerRequest;
use App\Form\OrganizerRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\NotificationService;

class OrganizerRequestController extends AbstractController
{

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    // Route permettant à un utilisateur de faire une demande pour devenir organisateur
    #[Route('/request-organizer', name: 'request_organizer')]
    public function request(Request $request, EntityManagerInterface $em)
    {
        // Vérifie que l'utilisateur est connecté et a au moins le rôle ROLE_USER
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        // Bloque l'accès aux utilisateurs ayant déjà le rôle ORGANIZER ou ADMIN
        if (in_array('ROLE_ORGANIZER', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Vous êtes déjà organisateur ou administrateur.');
            return $this->redirectToRoute('homepage');
        }

        // Vérifie si une demande "en attente" est déjà associée à l'utilisateur
        foreach ($user->getOrganizerRequests() as $requestExisting) {
            if ($requestExisting->getStatus() === 'pending') {
                $this->addFlash('info', 'Votre demande est déjà en cours de traitement.');
                return $this->redirectToRoute('homepage');
            }
        }

        // Crée une nouvelle demande de rôle organisateur
        $organizerRequest = new OrganizerRequest();
        $organizerRequest->setUser($user);

        // Création du formulaire de demande
        $form = $this->createForm(OrganizerRequestType::class, $organizerRequest);
        $form->handleRequest($request);

        // Traitement du formulaire s’il est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($organizerRequest); // Préparation à l'insertion en base
            $em->flush();                    // Exécution de l'insertion

            $this->addFlash('success', 'Votre demande a été envoyée.');

            // Envoi de notifications à l'admin et à l'utilisateur (supposé que le service est injecté)
            $this->notificationService->notifyAdminNewRequest($organizerRequest);
            $this->notificationService->notifyUserRequestSent($user);

            return $this->redirectToRoute('homepage');
        }

        // Affichage du formulaire de demande
        return $this->render('organizer_request/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
