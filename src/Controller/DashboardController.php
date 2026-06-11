<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Entity\ExerciseLoadProfile;
use App\Entity\GymEquipment;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutSession;
use App\Service\CurrentUserProvider;
use App\Service\GymProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        CurrentUserProvider $currentUserProvider,
        GymProfileProvider $gymProfileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        $gymProfile = $gymProfileProvider->getCurrentGym();

        return $this->render('dashboard/index.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'equipmentCount' => $entityManager->getRepository(Equipment::class)->count([]),
            'exerciseCount' => $entityManager->getRepository(Exercise::class)->count([]),
            'availableEquipmentCount' => $entityManager->getRepository(GymEquipment::class)->count([
                'gymProfile' => $gymProfile,
                'isAvailable' => true,
            ]),
            'workoutPlanCount' => $entityManager->getRepository(WorkoutPlan::class)->count([
                'appUser' => $currentUserProvider->getUser(),
            ]),
            'workoutSessionCount' => $entityManager->getRepository(WorkoutSession::class)->count([
                'appUser' => $currentUserProvider->getUser(),
            ]),
            'loadProfileCount' => $entityManager->getRepository(ExerciseLoadProfile::class)->count([
                'appUser' => $currentUserProvider->getUser(),
            ]),
        ]);
    }
}
