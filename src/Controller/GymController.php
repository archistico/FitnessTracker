<?php

namespace App\Controller;

use App\Entity\GymEquipment;
use App\Service\CurrentUserProvider;
use App\Service\GymProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gym')]
final class GymController extends AbstractController
{
    #[Route('/equipment', name: 'app_gym_equipment', methods: ['GET'])]
    public function equipment(
        GymProfileProvider $gymProfileProvider,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): Response {
        $gymProfile = $gymProfileProvider->getCurrentGym();
        $items = $entityManager->getRepository(GymEquipment::class)->findBy(
            ['gymProfile' => $gymProfile],
            ['id' => 'ASC']
        );

        return $this->render('gym/equipment.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'gymProfile' => $gymProfile,
            'items' => $items,
        ]);
    }

    #[Route('/equipment/{id}/toggle', name: 'app_gym_equipment_toggle', methods: ['POST'])]
    public function toggleEquipment(
        GymEquipment $gymEquipment,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('toggle_gym_equipment_'.$gymEquipment->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valido.');
        }

        $gymEquipment->setIsAvailable(!$gymEquipment->isAvailable());
        $entityManager->flush();

        return $this->redirectToRoute('app_gym_equipment');
    }
}
