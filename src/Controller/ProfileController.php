<?php

namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Repository\UserRepository;

class ProfileController extends AbstractController
{
    // Permet à l'utilisateur connecté de modifier son profil
    #[Route('/profile', name: 'profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        // Récupère l'utilisateur actuellement connecté
        $user = $this->getUser();

        // Vérifie que l'utilisateur est connecté
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Création du formulaire de profil avec les données de l'utilisateur
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        // Traitement du formulaire s’il est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush(); // Met à jour l'utilisateur dans la base de données

            // Réinitialisation du champ file pour éviter un bug d'upload (notamment avec VichUploader)
            $user->setProfileImageFile(null);

            $this->addFlash('success', 'Profil mis à jour avec succès.');

            // Redirection vers la même page (PRG pattern)
            return $this->redirectToRoute('profile_edit');
        }

        // Affichage du formulaire de modification du profil
        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    // Affiche le profil public d’un utilisateur via son nom d’utilisateur (username)
    #[Route('/profile/{username}', name: 'profile_show')]
    public function show(string $username, UserRepository $userRepository): Response
    {
        // Recherche d’un utilisateur via son nom d’utilisateur
        $user = $userRepository->findOneBy(['username' => $username]);

        // Si aucun utilisateur trouvé, renvoie une erreur 404
        if (!$user) {
            throw $this->createNotFoundException("Aucun utilisateur trouvé pour '$username'.");
        }

        // Récupération des événements créés par l'utilisateur (relation OneToMany)
        $events = $user->getEvents();

        // Affichage du profil et de ses événements
        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'events' => $events,
        ]);
    }
}
