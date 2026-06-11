<?php

namespace App\Controller;

use App\Entity\ExerciseSession;
use App\Entity\SetLog;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutSession;
use App\Enum\PerceivedEffort;
use App\Enum\PerceivedLoad;
use App\Enum\WorkoutSessionStatus;
use App\Service\CurrentUserProvider;
use App\Service\WorkoutSessionFactory;
use App\Service\WorkoutSessionSummary;
use App\Service\TreninoExerciseEvaluationService;
use App\Service\SetLoadAdjustmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workout-sessions')]
final class WorkoutSessionController extends AbstractController
{
    #[Route('', name: 'app_workout_session_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $currentUser = $currentUserProvider->getUser();

        /** @var list<WorkoutSession> $sessions */
        $sessions = $entityManager->getRepository(WorkoutSession::class)->findBy(
            ['appUser' => $currentUser],
            ['sessionDate' => 'DESC', 'id' => 'DESC'],
            50
        );

        return $this->render('workout_session/index.html.twig', [
            'currentUser' => $currentUser,
            'sessions' => $sessions,
        ]);
    }

    #[Route('/start/{slug}', name: 'app_workout_session_start_from_plan', methods: ['POST'])]
    public function startFromPlan(
        string $slug,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        WorkoutSessionFactory $workoutSessionFactory,
    ): RedirectResponse {
        $currentUser = $currentUserProvider->getUser();
        $plan = $entityManager->getRepository(WorkoutPlan::class)->findOneBy([
            'slug' => $slug,
            'appUser' => $currentUser,
        ]);

        if (!$plan instanceof WorkoutPlan) {
            throw $this->createNotFoundException('Scheda non trovata.');
        }

        if ($plan->getExercises()->count() === 0) {
            $this->addFlash('warning', 'Non puoi avviare una scheda senza esercizi.');

            return $this->redirectToRoute('app_workout_plan_show', ['slug' => $slug]);
        }

        $session = $workoutSessionFactory->createFromPlan($currentUser, $plan);
        $entityManager->persist($session);
        $entityManager->flush();

        $this->addFlash('success', 'Allenamento avviato. Ora puoi registrare le serie reali.');

        return $this->redirectToRoute('app_workout_session_show', ['id' => $session->getId()]);
    }

    #[Route('/{id}', name: 'app_workout_session_show', methods: ['GET'])]
    public function show(
        int $id,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        WorkoutSessionSummary $workoutSessionSummary,
        TreninoExerciseEvaluationService $treninoExerciseEvaluationService,
        SetLoadAdjustmentService $setLoadAdjustmentService,
    ): Response {
        $session = $this->findSession($entityManager, $currentUserProvider, $id);

        return $this->render('workout_session/show.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'session' => $session,
            'summary' => $workoutSessionSummary->summarize($session),
            'treninoEvaluations' => $this->buildTreninoEvaluations($session, $treninoExerciseEvaluationService),
            'setLoadAdjustments' => $setLoadAdjustmentService->buildForSession($session),
            'setWeightSuggestions' => $setLoadAdjustmentService->buildWeightSuggestionsForSession($session),
            'nextOpenSetId' => $this->findNextOpenSetId($session),
            'perceivedLoads' => PerceivedLoad::selectableCases(),
            'perceivedEfforts' => PerceivedEffort::cases(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_workout_session_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): Response {
        $session = $this->findSession($entityManager, $currentUserProvider, $id);
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillSessionFromRequest($session, $request);

            if ($formErrors === []) {
                $entityManager->flush();

                $this->addFlash('success', 'Dati allenamento aggiornati.');

                return $this->redirectToRoute('app_workout_session_show', ['id' => $session->getId()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->render('workout_session/form.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'session' => $session,
            'statuses' => WorkoutSessionStatus::cases(),
            'formErrors' => $formErrors,
            'formData' => $formData,
            'formSubmitted' => $formSubmitted,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_workout_session_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): RedirectResponse {
        $session = $this->findSession($entityManager, $currentUserProvider, $id);

        if (!$this->isCsrfTokenValid('delete_workout_session_'.$session->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valido.');
        }

        $entityManager->remove($session);
        $entityManager->flush();

        $this->addFlash('warning', 'Sessione eliminata dal diario.');

        return $this->redirectToRoute('app_workout_session_index');
    }

    #[Route('/{sessionId}/sets/{setId}', name: 'app_workout_session_update_set', methods: ['POST'])]
    public function updateSet(
        int $sessionId,
        int $setId,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        SetLoadAdjustmentService $setLoadAdjustmentService,
    ): RedirectResponse {
        $session = $this->findSession($entityManager, $currentUserProvider, $sessionId);
        $setLog = $entityManager->getRepository(SetLog::class)->find($setId);

        if (!$setLog instanceof SetLog || $setLog->getExerciseSession()->getWorkoutSession() !== $session) {
            throw $this->createNotFoundException('Serie non trovata.');
        }

        $rir = $this->nullableFloat($request->request->get('rir'));

        $setLog
            ->setSkipped(false)
            ->setActualWeightKg($this->nullableFloat($request->request->get('actualWeightKg')))
            ->setActualReps($this->nullableInt($request->request->get('actualReps')))
            ->setActualDurationSeconds($this->nullableInt($request->request->get('actualDurationSeconds')))
            ->setActualDistanceMeters($this->nullableFloat($request->request->get('actualDistanceMeters')))
            ->setActualResistanceLevel($this->nullableInt($request->request->get('actualResistanceLevel')))
            ->setRir($rir)
            ->setReachedFailure($rir !== null && $rir <= 0.0)
            ->setPerceivedLoad($this->nullablePerceivedLoad($request->request->get('perceivedLoad')))
            ->setPerceivedEffort($this->nullablePerceivedEffort($request->request->get('perceivedEffort')))
            ->setRestSecondsActual($this->nullableInt($request->request->get('restSecondsActual')))
            ->setNotes($request->request->get('notes') !== null ? (string) $request->request->get('notes') : null);

        $setLog->getExerciseSession()->refreshStatusFromSetData();
        $updatedNextSetLog = $setLoadAdjustmentService->applySuggestedWeightToNextOpenCompatibleSet($setLog);
        $entityManager->flush();

        if ($updatedNextSetLog instanceof SetLog && $updatedNextSetLog->getTargetWeightKg() !== null) {
            $this->addFlash(
                'success',
                sprintf(
                    'Serie %d aggiornata. Ho aggiornato il carico suggerito della serie %d a %s kg.',
                    $setLog->getSetNumber(),
                    $updatedNextSetLog->getSetNumber(),
                    rtrim(rtrim(number_format($updatedNextSetLog->getTargetWeightKg(), 1, ',', ''), '0'), ',')
                )
            );
        } else {
            $this->addFlash('success', sprintf('Serie %d aggiornata.', $setLog->getSetNumber()));
        }

        return $this->redirectToRoute('app_workout_session_show', [
            'id' => $session->getId(),
            '_fragment' => $this->nextOpenSetFragmentOrCurrentSet($session, $updatedNextSetLog ?? $setLog),
        ]);
    }


    #[Route('/{sessionId}/sets/{setId}/skip', name: 'app_workout_session_skip_set', methods: ['POST'])]
    public function skipSet(
        int $sessionId,
        int $setId,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): RedirectResponse {
        $session = $this->findSession($entityManager, $currentUserProvider, $sessionId);
        $setLog = $entityManager->getRepository(SetLog::class)->find($setId);

        if (!$setLog instanceof SetLog || $setLog->getExerciseSession()->getWorkoutSession() !== $session) {
            throw $this->createNotFoundException('Serie non trovata.');
        }

        $reason = $request->request->get('skipReason') !== null ? (string) $request->request->get('skipReason') : null;
        $setLog->setSkipped(true)->setSkipReason($reason);
        $setLog->getExerciseSession()->refreshStatusFromSetData();
        $entityManager->flush();

        $this->addFlash('warning', sprintf('Serie %d segnata come saltata.', $setLog->getSetNumber()));

        return $this->redirectToRoute('app_workout_session_show', [
            'id' => $session->getId(),
            '_fragment' => $this->nextOpenSetFragmentOrCurrentSet($session, $setLog),
        ]);
    }

    #[Route('/{sessionId}/exercises/{exerciseSessionId}/skip', name: 'app_workout_session_skip_exercise', methods: ['POST'])]
    public function skipExercise(
        int $sessionId,
        int $exerciseSessionId,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): RedirectResponse {
        $session = $this->findSession($entityManager, $currentUserProvider, $sessionId);
        $exerciseSession = $entityManager->getRepository(ExerciseSession::class)->find($exerciseSessionId);

        if (!$exerciseSession instanceof ExerciseSession || $exerciseSession->getWorkoutSession() !== $session) {
            throw $this->createNotFoundException('Esercizio non trovato.');
        }

        $reason = $request->request->get('skipReason') !== null ? (string) $request->request->get('skipReason') : null;
        $exerciseSession->markSkipped($reason);
        $entityManager->flush();

        $this->addFlash('warning', sprintf('Esercizio "%s" segnato come saltato.', $exerciseSession->getExercise()->getName()));

        return $this->redirectToRoute('app_workout_session_show', ['id' => $session->getId(), '_fragment' => 'exercise-' . $exerciseSession->getId()]);
    }

    #[Route('/{id}/complete', name: 'app_workout_session_complete', methods: ['POST'])]
    public function complete(
        int $id,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        TreninoExerciseEvaluationService $treninoExerciseEvaluationService,
    ): RedirectResponse {
        $session = $this->findSession($entityManager, $currentUserProvider, $id);
        $session->closeFromSetData();
        $entityManager->flush();

        $this->addFlash('success', sprintf('Allenamento chiuso come %s.', strtolower($session->getStatus()->label())));
        foreach ($this->buildTreninoEvaluations($session, $treninoExerciseEvaluationService) as $exerciseSessionId => $evaluation) {
            $this->addFlash('info', sprintf('Trenino esercizio #%d: %s. Prossimo step: %d.', $exerciseSessionId, $evaluation->getLabel(), $evaluation->getNextStep()));
        }

        return $this->redirectToRoute('app_workout_session_show', ['id' => $session->getId()]);
    }


    private function nextOpenSetFragmentOrCurrentSet(WorkoutSession $session, ?SetLog $currentSetLog = null): string
    {
        $nextOpenSetId = $this->findNextOpenSetId($session);
        if ($nextOpenSetId !== null) {
            return 'set-' . $nextOpenSetId;
        }

        if ($currentSetLog instanceof SetLog && $currentSetLog->getId() !== null) {
            return 'set-' . $currentSetLog->getId();
        }

        return 'top';
    }

    private function findNextOpenSetId(WorkoutSession $session): ?int
    {
        foreach ($session->getExerciseSessions() as $exerciseSession) {
            if ($exerciseSession->getStatus()->value === 'skipped') {
                continue;
            }

            foreach ($exerciseSession->getSetLogs() as $setLog) {
                if (!$setLog->isSkipped() && !$setLog->hasActualData()) {
                    return $setLog->getId();
                }
            }
        }

        return null;
    }

    /** @return array<int, \App\Dto\TreninoExerciseEvaluation> */
    private function buildTreninoEvaluations(WorkoutSession $session, TreninoExerciseEvaluationService $treninoExerciseEvaluationService): array
    {
        $evaluations = [];
        foreach ($session->getExerciseSessions() as $exerciseSession) {
            $evaluation = $treninoExerciseEvaluationService->evaluate($exerciseSession);
            if ($evaluation !== null && $exerciseSession->getId() !== null) {
                $evaluations[$exerciseSession->getId()] = $evaluation;
            }
        }

        return $evaluations;
    }

    private function findSession(EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider, int $id): WorkoutSession
    {
        $session = $entityManager->getRepository(WorkoutSession::class)->findOneBy([
            'id' => $id,
            'appUser' => $currentUserProvider->getUser(),
        ]);

        if (!$session instanceof WorkoutSession) {
            throw $this->createNotFoundException('Allenamento non trovato.');
        }

        return $session;
    }

    /** @return list<string> */
    private function fillSessionFromRequest(WorkoutSession $session, Request $request): array
    {
        $errors = [];

        $sessionDate = $this->parseDate((string) $request->request->get('sessionDate'));
        if (!$sessionDate instanceof \DateTimeImmutable) {
            $errors['sessionDate'] = 'La data allenamento non è valida.';
        }

        $startedAt = $this->parseDateTime((string) $request->request->get('startedAt'));
        if (!$startedAt instanceof \DateTimeImmutable) {
            $errors['startedAt'] = 'L’orario di inizio non è valido.';
        }

        $endedAtValue = trim((string) $request->request->get('endedAt'));
        $endedAt = $endedAtValue !== '' ? $this->parseDateTime($endedAtValue) : null;
        if ($endedAtValue !== '' && !$endedAt instanceof \DateTimeImmutable) {
            $errors['endedAt'] = 'L’orario di fine non è valido.';
        }

        $status = WorkoutSessionStatus::tryFrom((string) $request->request->get('status'));
        if (!$status instanceof WorkoutSessionStatus) {
            $errors['status'] = 'Lo stato della sessione non è valido.';
        }

        if ($startedAt instanceof \DateTimeImmutable && $endedAt instanceof \DateTimeImmutable && $endedAt < $startedAt) {
            $errors['endedAt'] = 'L’orario di fine non può essere precedente all’orario di inizio.';
        }

        if ($errors !== []) {
            return $errors;
        }

        $notes = trim((string) $request->request->get('notes'));

        $session
            ->setSessionDate($sessionDate)
            ->setStartedAt($startedAt)
            ->setEndedAt($endedAt)
            ->setStatus($status)
            ->setNotes($notes !== '' ? $notes : null);

        return [];
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function parseDateTime(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value);

        return $dateTime instanceof \DateTimeImmutable ? $dateTime : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private function nullablePerceivedLoad(mixed $value): ?PerceivedLoad
    {
        return $value !== null && trim((string) $value) !== '' ? PerceivedLoad::tryFrom((string) $value) : null;
    }

    private function nullablePerceivedEffort(mixed $value): ?PerceivedEffort
    {
        return $value !== null && trim((string) $value) !== '' ? PerceivedEffort::tryFrom((string) $value) : null;
    }
}
