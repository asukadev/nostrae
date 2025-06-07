<?php

namespace App\Controller\Admin;

use App\Entity\OrganizerRequest;
use App\Repository\OrganizerRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class OrganizerRequestAdminController extends AbstractController
{
    #[Route('/admin/organizer-requests', name: 'admin_organizer_requests')]
    public function list(OrganizerRequestRepository $repository)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $requests = $repository->findBy(['status' => 'pending']);

        return $this->render('admin/organizer_requests/list.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/admin/organizer-request/{id}/accept', name: 'admin_organizer_request_accept')]
    public function accept(OrganizerRequest $request, EntityManagerInterface $em, UserRepository $userRepository)
    {
        $user = $request->getUser();
        $roles = $user->getRoles();
        $roles[] = 'ROLE_ORGANIZER';
        $user->setRoles(array_unique($roles));

        $request->setStatus('accepted');
        
        $em->flush();

        $this->notificationService->notifyUserRequestStatus($user, 'accepted');
        $this->addFlash('success', 'Demande acceptée !');
        return $this->redirectToRoute('admin_organizer_requests');
    }

    #[Route('/admin/organizer-request/{id}/refuse', name: 'admin_organizer_request_refuse')]
    public function refuse(OrganizerRequest $request, EntityManagerInterface $em)
    {
        $request->setStatus('refused');
        $em->flush();

        $this->notificationService->notifyUserRequestStatus($user, 'refused');
        $this->addFlash('info', 'Demande refusée.');
        return $this->redirectToRoute('admin_organizer_requests');
    }
}
