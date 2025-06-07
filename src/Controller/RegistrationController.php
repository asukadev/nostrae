<?php

namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\NotificationService;
use App\Entity\OrganizerRequest;

// Route associée à ce contrôleur (singleton via __invoke)
#[Route('/register', name: 'app_register')]
class RegistrationController extends AbstractController
{
    // Injection du service de notification via le constructeur
    public function __construct(
        private NotificationService $notificationService
    ) {}

    // Méthode __invoke() appelée automatiquement pour cette route
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        // Création d’un nouvel utilisateur vide
        $user = new User();

        // Création du formulaire d'inscription
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on traite l'inscription
        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe fourni via le champ plainPassword
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Si l'utilisateur souhaite devenir organisateur
            if ($form->get('becomeOrganizer')->getData()) {
                // Création d'une demande d'organisateur liée à l'utilisateur
                $organizerRequest = new OrganizerRequest();
                $organizerRequest->setUser($user);

                // Récupération de la motivation (optionnelle)
                $motivation = $form->get('motivation')->getData() ?? 'Pas de motivation';
                $organizerRequest->setMotivation($motivation);

                // Préparation de la demande pour insertion
                $em->persist($organizerRequest);
            }

            // Préparation de l'utilisateur pour insertion
            $em->persist($user);
            $em->flush(); // Insertion des données dans la base

            // Envoi d’un email ou d’une notification via le service
            $this->notificationService->sendRegistration($user);

            // Message flash de succès
            $this->addFlash('success', 'Compte créé avec succès !');

            // Redirection vers la page de connexion
            return $this->redirectToRoute('app_login');
        }

        // Affichage du formulaire d'inscription
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
