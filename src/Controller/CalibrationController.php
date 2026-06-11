<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\ExerciseLoadProfile;
use App\Entity\WorkoutSession;
use App\Enum\ExerciseTrackingMode;
use App\Enum\WorkoutSessionType;
use App\Service\CalibrationSessionFactory;
use App\Service\CurrentUserProvider;
use App\Service\LoadEstimationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/calibrations')]
final class CalibrationController extends AbstractController
{
    #[Route('', name: 'app_calibration_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $currentUser = $currentUserProvider->getUser();

        /** @var list<Exercise> $exercises */
        $exercises = $entityManager->getRepository(Exercise::class)->findBy([], ['name' => 'ASC']);
        $profilesByExerciseId = [];

        foreach ($exercises as $exercise) {
            if ($exercise->getId() === null) {
                continue;
            }

            $profile = $entityManager->getRepository(ExerciseLoadProfile::class)->findOneBy(
                ['appUser' => $currentUser, 'exercise' => $exercise],
                ['validFrom' => 'DESC', 'id' => 'DESC']
            );

            if ($profile instanceof ExerciseLoadProfile) {
                $profilesByExerciseId[$exercise->getId()] = $profile;
            }
        }

        return $this->render('calibration/index.html.twig', [
            'currentUser' => $currentUser,
            'exercises' => $exercises,
            'profilesByExerciseId' => $profilesByExerciseId,
        ]);
    }

    #[Route('/start/{slug}', name: 'app_calibration_start', methods: ['POST'])]
    public function start(
        string $slug,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        CalibrationSessionFactory $calibrationSessionFactory,
    ): RedirectResponse {
        $currentUser = $currentUserProvider->getUser();
        $exercise = $entityManager->getRepository(Exercise::class)->findOneBy(['slug' => $slug]);

        if (!$exercise instanceof Exercise) {
            throw $this->createNotFoundException('Esercizio non trovato.');
        }

        if (!$exercise->getTrackingMode()->usesWeight()) {
            $this->addFlash('warning', 'Per ora la calibrazione iniziale dei carichi è disponibile solo per esercizi con kg e reps.');

            return $this->redirectToRoute('app_calibration_index');
        }

        $session = $calibrationSessionFactory->createForExercise($currentUser, $exercise);
        $entityManager->persist($session);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Calibrazione avviata per %s. Registra le serie test nel diario.', $exercise->getName()));

        return $this->redirectToRoute('app_workout_session_show', ['id' => $session->getId()]);
    }

    #[Route('/sessions/{id}/complete', name: 'app_calibration_complete', methods: ['POST'])]
    public function complete(
        int $id,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        LoadEstimationService $loadEstimationService,
    ): RedirectResponse {
        $currentUser = $currentUserProvider->getUser();
        $session = $entityManager->getRepository(WorkoutSession::class)->findOneBy([
            'id' => $id,
            'appUser' => $currentUser,
            'sessionType' => WorkoutSessionType::Calibration,
        ]);

        if (!$session instanceof WorkoutSession) {
            throw $this->createNotFoundException('Sessione di calibrazione non trovata.');
        }

        try {
            $profile = $loadEstimationService->buildInitialLoadProfile($currentUser, $session);
        } catch (\InvalidArgumentException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_workout_session_show', ['id' => $session->getId()]);
        }

        $entityManager->persist($profile);
        $session->closeFromSetData();
        $entityManager->flush();

        $this->addFlash('success', 'Calibrazione finalizzata. Ho salvato il profilo carichi iniziale per l’esercizio.');

        return $this->redirectToRoute('app_calibration_index');
    }
}
