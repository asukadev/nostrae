<?php

namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\Event;
use App\Form\EventSearchType;
use App\Repository\EventRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    // Page d’accueil du site avec recherche d’événements
    #[Route('/', name: 'homepage')]
    public function homepage(Request $request, EventRepository $eventRepository, PaginatorInterface $paginator): Response
    {
        // Création du formulaire de recherche d’événements
        $form = $this->createForm(EventSearchType::class, null, [
            'method' => 'GET',           // Le formulaire utilise la méthode GET
            'csrf_protection' => false, // Pas de protection CSRF pour un formulaire non sensible
        ]);
        $form->handleRequest($request); // Récupération des données de la requête

        // Construction d’un QueryBuilder de base (non utilisé ici, peut être supprimé si inutile)
        $qb = $eventRepository->createQueryBuilder('e')
            ->leftJoin('e.location', 'l')
            ->leftJoin('e.type', 't')
            ->addSelect('l', 't')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults(100); // Limite de sécurité en cas d'affichage brut sans pagination

        // Récupération des critères du formulaire
        $criteria = $form->getData();

        // Création de la requête DQL en fonction des critères saisis
        $query = $eventRepository->searchQueryBuilder($criteria ?? []);

        // Pagination de la requête obtenue
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Numéro de page depuis la requête GET (par défaut : 1)
            5 // Nombre d’événements par page
        );

        // Affichage de la page d’accueil avec les événements et le formulaire de recherche
        return $this->render('site/homepage.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $form->createView(),
        ]);
    }

    // Page de contact
    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        // Rend la vue statique de contact
        return $this->render('site/contact.html.twig');
    }

    // Page "À propos"
    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        // Rend la vue statique de présentation
        return $this->render('site/about.html.twig');
    }

    // Page d'informations diverses
    #[Route('/information', name: 'information')]
    public function information(): Response
    {
        // Rend la vue statique d'informations complémentaires
        return $this->render('site/information.html.twig');
    }
}
