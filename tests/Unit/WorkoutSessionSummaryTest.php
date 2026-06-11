<?php

namespace App\Tests\Unit;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutPlanExercise;
use App\Enum\ExerciseSessionStatus;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ProgressionType;
use App\Enum\WorkoutSessionStatus;
use App\Service\WorkoutSessionFactory;
use App\Service\WorkoutSessionSummary;
use PHPUnit\Framework\TestCase;

final class WorkoutSessionSummaryTest extends TestCase
{
    public function testSummarySeparatesCompletedSkippedAndRemainingSets(): void
    {
        $session = $this->createSessionWithThreeSets();
        $exerciseSession = $session->getExerciseSessions()->first();

        $firstSet = $exerciseSession->getSetLogs()->get(0);
        $firstSet->setActualWeightKg(80)->setActualReps(8)->setRir(2);

        $secondSet = $exerciseSession->getSetLogs()->get(1);
        $secondSet->setSkipped(true)->setSkipReason('Poco tempo');

        $summary = (new WorkoutSessionSummary())->summarize($session);

        self::assertSame(3, $summary['plannedSets']);
        self::assertSame(1, $summary['completedSets']);
        self::assertSame(1, $summary['skippedSets']);
        self::assertSame(1, $summary['remainingSets']);
        self::assertSame(8, $summary['totalReps']);
        self::assertSame(640.0, $summary['totalWeightVolume']);
        self::assertSame(2.0, $summary['averageRir']);
    }

    public function testSkippedExerciseMarksAllItsSetsAndSessionClosesPartial(): void
    {
        $session = $this->createSessionWithThreeSets();
        $exerciseSession = $session->getExerciseSessions()->first();

        $exerciseSession->markSkipped('Dolore');
        $session->closeFromSetData();

        self::assertSame(ExerciseSessionStatus::Skipped, $exerciseSession->getStatus());
        self::assertSame('Dolore', $exerciseSession->getSkipReason());
        self::assertSame(WorkoutSessionStatus::Partial, $session->getStatus());
        self::assertSame(3, $session->getSkippedSetCount());
    }

    private function createSessionWithThreeSets(): \App\Entity\WorkoutSession
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

        $plan->addExercise(
            (new WorkoutPlanExercise())
                ->setExercise($exercise)
                ->setPosition(1)
                ->setProgressionType(ProgressionType::Fixed)
                ->setPlannedSets(3)
                ->setPlannedRepMin(8)
                ->setPlannedRepMax(10)
        );

        return (new WorkoutSessionFactory())->createFromPlan($user, $plan);
    }
}
