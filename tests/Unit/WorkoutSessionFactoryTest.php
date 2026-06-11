<?php

namespace App\Tests\Unit;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutPlanExercise;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ProgressionType;
use App\Enum\WorkoutSessionStatus;
use App\Enum\WorkoutSessionType;
use App\Service\WorkoutSessionFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutSessionFactoryTest extends TestCase
{
    public function testCreateFromPlanCopiesPlannedExercisesAndSets(): void
    {
        $user = (new AppUser())->setName('Utente demo')->setIsDefault(true);
        $exercise = (new Exercise())
            ->setName('Panca piana')
            ->setSlug('panca-piana')
            ->setDescription('Test')
            ->setTrackingMode(ExerciseTrackingMode::WeightReps);

        $plan = (new WorkoutPlan())
            ->setAppUser($user)
            ->setName('Scheda test')
            ->setSlug('scheda-test');

        $planExercise = (new WorkoutPlanExercise())
            ->setExercise($exercise)
            ->setPosition(1)
            ->setProgressionType(ProgressionType::Fixed)
            ->setPlannedSets(3)
            ->setPlannedRepMin(8)
            ->setPlannedRepMax(10)
            ->setPlannedRestSeconds(150);
        $plan->addExercise($planExercise);

        $session = (new WorkoutSessionFactory())->createFromPlan($user, $plan, new \DateTimeImmutable('2026-06-11 20:00:00'));

        self::assertSame(WorkoutSessionType::Training, $session->getSessionType());
        self::assertSame(WorkoutSessionStatus::InProgress, $session->getStatus());
        self::assertSame(1, $session->getExerciseSessions()->count());

        $exerciseSession = $session->getExerciseSessions()->first();
        self::assertSame($exercise, $exerciseSession->getExercise());
        self::assertSame(3, $exerciseSession->getSetLogs()->count());

        $firstSet = $exerciseSession->getSetLogs()->first();
        self::assertSame(1, $firstSet->getSetNumber());
        self::assertSame(8, $firstSet->getTargetRepMin());
        self::assertSame(10, $firstSet->getTargetRepMax());
        self::assertSame(150, $firstSet->getRestSecondsPlanned());
    }

    public function testExerciseWithoutPlannedSetsCreatesOneLogRow(): void
    {
        $user = (new AppUser())->setName('Utente demo')->setIsDefault(true);
        $exercise = (new Exercise())
            ->setName('Cyclette')
            ->setSlug('cyclette')
            ->setDescription('Test')
            ->setTrackingMode(ExerciseTrackingMode::CardioMachine);

        $plan = (new WorkoutPlan())
            ->setAppUser($user)
            ->setName('Cardio')
            ->setSlug('cardio');

        $plan->addExercise(
            (new WorkoutPlanExercise())
                ->setExercise($exercise)
                ->setPosition(1)
                ->setProgressionType(ProgressionType::Cardio)
                ->setPlannedDurationSeconds(600)
        );

        $session = (new WorkoutSessionFactory())->createFromPlan($user, $plan);
        $exerciseSession = $session->getExerciseSessions()->first();
        $setLog = $exerciseSession->getSetLogs()->first();

        self::assertSame(1, $exerciseSession->getSetLogs()->count());
        self::assertSame(600, $setLog->getTargetDurationSeconds());
    }
    public function testTreninoInvictusExerciseCreatesStepOneSetPattern(): void
    {
        $user = (new AppUser())->setName('Utente demo')->setIsDefault(true);
        $exercise = (new Exercise())
            ->setName('Panca piana')
            ->setSlug('panca-piana')
            ->setDescription('Test')
            ->setTrackingMode(ExerciseTrackingMode::WeightReps);

        $plan = (new WorkoutPlan())
            ->setAppUser($user)
            ->setName('Scheda Trenino')
            ->setSlug('scheda-trenino');

        $plan->addExercise(
            (new WorkoutPlanExercise())
                ->setExercise($exercise)
                ->setPosition(1)
                ->setProgressionType(ProgressionType::TreninoInvictus)
        );

        $session = (new WorkoutSessionFactory())->createFromPlan($user, $plan);
        $exerciseSession = $session->getExerciseSessions()->first();
        $sets = $exerciseSession->getSetLogs()->toArray();

        self::assertSame(ProgressionType::TreninoInvictus, $exerciseSession->getProgressionType());
        self::assertSame(1, $exerciseSession->getProgressionStepNumber());
        self::assertCount(4, $sets);
        self::assertSame(8, $sets[0]->getTargetRepMin());
        self::assertSame(10, $sets[0]->getTargetRepMax());
        self::assertSame(8, $sets[1]->getTargetRepMin());
        self::assertSame(10, $sets[1]->getTargetRepMax());
        self::assertSame(10, $sets[2]->getTargetRepMin());
        self::assertSame(12, $sets[2]->getTargetRepMax());
        self::assertSame(10, $sets[3]->getTargetRepMin());
        self::assertSame(12, $sets[3]->getTargetRepMax());
    }

}
