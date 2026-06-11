<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\ExerciseSession;
use App\Entity\SetLog;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutPlanExercise;
use App\Entity\WorkoutSession;
use App\Enum\ProgressionType;
use App\Enum\WorkoutSessionStatus;
use App\Enum\WorkoutSessionType;

final class WorkoutSessionFactory
{
    public function __construct(
        private readonly ?TreninoInvictusProgressionService $treninoInvictusProgressionService = null,
        private readonly ?LoadSuggestionService $loadSuggestionService = null,
        private readonly ?TreninoStepResolver $treninoStepResolver = null,
    ) {
    }

    public function createFromPlan(AppUser $appUser, WorkoutPlan $plan, ?\DateTimeImmutable $startedAt = null): WorkoutSession
    {
        $startedAt ??= new \DateTimeImmutable();

        $session = (new WorkoutSession())
            ->setAppUser($appUser)
            ->setWorkoutPlan($plan)
            ->setSessionType(WorkoutSessionType::Training)
            ->setStatus(WorkoutSessionStatus::InProgress)
            ->setSessionDate($startedAt)
            ->setStartedAt($startedAt)
            ->setNotes($plan->getName());

        foreach ($plan->getExercises() as $planExercise) {
            $exerciseSession = $this->createExerciseSession($appUser, $planExercise);
            $session->addExerciseSession($exerciseSession);
        }

        return $session;
    }

    private function createExerciseSession(AppUser $appUser, WorkoutPlanExercise $planExercise): ExerciseSession
    {
        $progressionStepNumber = $planExercise->getProgressionType() === ProgressionType::TreninoInvictus
            ? $this->resolveTreninoStepNumber($appUser, $planExercise)
            : null;

        $exerciseSession = (new ExerciseSession())
            ->setExercise($planExercise->getExercise())
            ->setWorkoutPlanExercise($planExercise)
            ->setPosition($planExercise->getPosition())
            ->setProgressionType($planExercise->getProgressionType())
            ->setProgressionStepNumber($progressionStepNumber)
            ->setNotes($this->buildExerciseSessionNotes($planExercise, $progressionStepNumber));

        if ($planExercise->getProgressionType() === ProgressionType::TreninoInvictus) {
            $this->addTreninoSetLogs($exerciseSession, $appUser, $planExercise, $progressionStepNumber ?? 1);

            return $exerciseSession;
        }

        $setCount = $planExercise->getPlannedSets() ?? 1;
        for ($setNumber = 1; $setNumber <= $setCount; ++$setNumber) {
            $exerciseSession->addSetLog(
                (new SetLog())
                    ->setSetNumber($setNumber)
                    ->setTargetRepMin($planExercise->getPlannedRepMin())
                    ->setTargetRepMax($planExercise->getPlannedRepMax())
                    ->setTargetWeightKg($this->suggestWeight($appUser, $planExercise, $planExercise->getPlannedRepMin(), $planExercise->getPlannedRepMax()))
                    ->setTargetDurationSeconds($planExercise->getPlannedDurationSeconds())
                    ->setRestSecondsPlanned($planExercise->getPlannedRestSeconds())
            );
        }

        return $exerciseSession;
    }

    private function resolveTreninoStepNumber(AppUser $appUser, WorkoutPlanExercise $planExercise): int
    {
        return $this->treninoStepResolver?->resolveNextStep($appUser, $planExercise) ?? 1;
    }

    private function addTreninoSetLogs(ExerciseSession $exerciseSession, AppUser $appUser, WorkoutPlanExercise $planExercise, int $stepNumber): void
    {
        $progressionService = $this->treninoInvictusProgressionService ?? new TreninoInvictusProgressionService();
        $setNumber = 1;

        foreach ($progressionService->getStepBlocks($stepNumber) as $block) {
            for ($i = 1; $i <= $block['setCount']; ++$i) {
                $exerciseSession->addSetLog(
                    (new SetLog())
                        ->setSetNumber($setNumber)
                        ->setTargetRepMin($block['repMin'])
                        ->setTargetRepMax($block['repMax'])
                        ->setTargetWeightKg($this->suggestWeight($appUser, $planExercise, $block['repMin'], $block['repMax']))
                        ->setRestSecondsPlanned($block['restSeconds'])
                );
                ++$setNumber;
            }
        }
    }

    private function suggestWeight(AppUser $appUser, WorkoutPlanExercise $planExercise, ?int $repMin, ?int $repMax): ?float
    {
        return $this->loadSuggestionService?->suggestWeightForRepRange($appUser, $planExercise->getExercise(), $repMin, $repMax);
    }

    private function buildExerciseSessionNotes(WorkoutPlanExercise $planExercise, ?int $progressionStepNumber): ?string
    {
        if ($planExercise->getProgressionType() !== ProgressionType::TreninoInvictus) {
            return $planExercise->getNotes();
        }

        $progressionService = $this->treninoInvictusProgressionService ?? new TreninoInvictusProgressionService();
        $summary = $progressionService->getStepSummary($progressionStepNumber ?? 1);

        return trim($summary . ($planExercise->getNotes() ? "\n" . $planExercise->getNotes() : ''));
    }
}
