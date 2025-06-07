<?php

namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\Location;
use App\Form\LocationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocationController extends AbstractController
{
    // Affiche la liste de tous les lieux
    #[Route('/location', name: 'location_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupération de tous les lieux depuis la base de données
        $locations = $em->getRepository(Location::class)->findAll();

        // Affichage de la vue avec la liste des lieux
        return $this->render('location/index.html.twig', [
            'locations' => $locations,
        ]);
    }

    // Création d’un nouveau lieu (accès réservé aux organisateurs ou admins)
    #[Route('/location/create', name: 'location_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        // Vérifie que l'utilisateur a les droits requis
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER', null, 'Accès refusé : seuls les organisateurs ou administrateurs peuvent effectuer cette action.');

        // Création d'une nouvelle instance de lieu
        $location = new Location();

        // Création du formulaire lié à l'entité Location
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        // Traitement du formulaire s’il est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($location); // Prépare l'insertion en base
            $em->flush();            // Exécute l'insertion

            $this->addFlash('success', 'Lieu créé avec succès !');

            // Redirection vers la même page pour recréer un nouveau lieu
            return $this->redirectToRoute('location_create');
        }

        // Affichage du formulaire de création
        return $this->render('location/create.html.twig', [
            'locationForm' => $form->createView(),
        ]);
    }

    // Édition d’un lieu existant
    #[Route('/location/edit/{id}', name: 'location_edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifie que l'utilisateur a les droits requis
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER', null, 'Accès refusé : seuls les organisateurs ou administrateurs peuvent effectuer cette action.');

        // Recherche du lieu par son identifiant
        $location = $em->getRepository(Location::class)->find($id);

        // Lieu non trouvé : on lève une exception 404
        if (!$location) {
            throw new NotFoundHttpException('Lieu non trouvé');
        }

        // Création du formulaire avec les données du lieu existant
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        // Traitement du formulaire si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush(); // Mise à jour de l’entité en base

            $this->addFlash('success', 'Lieu mis à jour avec succès.');

            // Redirection vers la liste des lieux
            return $this->redirectToRoute('location_index');
        }

        // Affichage du formulaire d'édition
        return $this->render('location/edit.html.twig', [
            'locationForm' => $form->createView(),
            'location' => $location,
        ]);
    }

    // Suppression d’un lieu
    #[Route('/location/delete/{id}', name: 'location_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        // Vérifie que l'utilisateur a les droits requis
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER', null, 'Accès refusé : seuls les organisateurs ou administrateurs peuvent effectuer cette action.');

        // Recherche du lieu à supprimer
        $location = $em->getRepository(Location::class)->find($id);

        // Lieu introuvable : exception 404
        if (!$location) {
            throw new NotFoundHttpException('Lieu introuvable');
        }

        // Vérifie la validité du token CSRF pour sécuriser la suppression
        if (!$this->isCsrfTokenValid('delete_location_' . $location->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('location_index');
        }

        // Suppression du lieu
        $em->remove($location);
        $em->flush();

        $this->addFlash('success', 'Lieu supprimé avec succès !');

        // Redirection vers la liste des lieux
        return $this->redirectToRoute('location_index');
    }
}
