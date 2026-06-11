<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\ExerciseSession;
use App\Entity\WorkoutPlanExercise;
use App\Enum\ProgressionType;
use App\Enum\WorkoutSessionStatus;
use Doctrine\ORM\EntityManagerInterface;

final class TreninoStepResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreninoExerciseEvaluationService $evaluationService,
    ) {
    }

    public function resolveNextStep(AppUser $appUser, WorkoutPlanExercise $planExercise): int
    {
        if ($planExercise->getProgressionType() !== ProgressionType::TreninoInvictus) {
            return 1;
        }

        $latestExerciseSession = $this->findLatestClosedExerciseSession($appUser, $planExercise);
        if (!$latestExerciseSession instanceof ExerciseSession) {
            return 1;
        }

        $evaluation = $this->evaluationService->evaluate($latestExerciseSession);

        return $evaluation?->getNextStep() ?? 1;
    }

    private function findLatestClosedExerciseSession(AppUser $appUser, WorkoutPlanExercise $planExercise): ?ExerciseSession
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('es')
            ->from(ExerciseSession::class, 'es')
            ->join('es.workoutSession', 'ws')
            ->where('ws.appUser = :appUser')
            ->andWhere('es.workoutPlanExercise = :planExercise')
            ->andWhere('es.progressionType = :progressionType')
            ->andWhere('ws.status IN (:closedStatuses)')
            ->orderBy('ws.sessionDate', 'DESC')
            ->addOrderBy('ws.id', 'DESC')
            ->addOrderBy('es.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('appUser', $appUser)
            ->setParameter('planExercise', $planExercise)
            ->setParameter('progressionType', ProgressionType::TreninoInvictus)
            ->setParameter('closedStatuses', [WorkoutSessionStatus::Completed, WorkoutSessionStatus::Partial])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
