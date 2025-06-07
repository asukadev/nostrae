<?php

namespace App\Controller;

// Importation des classes nécessaires
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    // Route pour afficher le formulaire de connexion
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('target_path'); // Remplacer target_path par une route réelle
        }

        // Récupère l'éventuelle erreur de connexion précédente
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier nom d'utilisateur saisi (utile pour pré-remplir le champ)
        $lastUsername = $authenticationUtils->getLastUsername();

        // Affiche le formulaire de connexion avec les données récupérées
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    // Route spéciale pour la déconnexion
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Ce contrôleur ne sera jamais exécuté.
        // Symfony intercepte cette route grâce à la configuration du firewall dans security.yaml
        throw new \LogicException('Cette méthode peut rester vide - elle est interceptée par la clé "logout" du firewall.');
    }
}
