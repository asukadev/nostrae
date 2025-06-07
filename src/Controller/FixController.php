<?php

namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FixController extends AbstractController
{
    // Route de correction pour générer les slugs manquants sur les événements existants
    #[Route('/fix-slugs', name: 'fix_slugs')]
    public function fix(
        EventRepository $eventRepository,
        SluggerInterface $slugger,
        EntityManagerInterface $em
    ) {
        // Vérifie que l'utilisateur a les droits administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupère tous les événements depuis la base de données
        $events = $eventRepository->findAll();

        // Parcourt chaque événement pour vérifier si un slug est déjà défini
        foreach ($events as $event) {
            // Si le slug est vide, on le génère à partir du titre de l'événement
            if (empty($event->getSlug())) {
                // Utilisation du slugger Symfony pour créer une version "URL friendly" du titre
                $slug = $slugger->slug($event->getTitle())->lower();
                $event->setSlug($slug);
            }
        }

        // Enregistre toutes les modifications dans la base de données
        $em->flush();

        // Retourne une réponse JSON pour confirmer que l’opération a bien été effectuée
        return $this->json([
            'message' => 'Slugs générés pour les événements existants.'
        ]);
    }
}
