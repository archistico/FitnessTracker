<?php

namespace App\Tests\Unit;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\ExerciseSession;
use App\Entity\SetLog;
use App\Entity\WorkoutSession;
use App\Enum\ExerciseTrackingMode;
use App\Enum\LoadProfileConfidence;
use App\Enum\WorkoutSessionType;
use App\Service\LoadEstimationService;
use PHPUnit\Framework\TestCase;

final class LoadEstimationServiceTest extends TestCase
{
    public function testEstimateOneRepMaxUsesRepsPlusRir(): void
    {
        $setLog = (new SetLog())
            ->setActualWeightKg(80)
            ->setActualReps(8)
            ->setRir(2);

        $estimate = (new LoadEstimationService())->estimateOneRepMaxFromSet($setLog);

        self::assertTrue($estimate['usedRir']);
        self::assertEqualsWithDelta(106.66, $estimate['oneRepMax'], 0.02);
    }

    public function testBuildInitialLoadProfileCreatesFourRepRanges(): void
    {
        $user = (new AppUser())->setName('Utente demo')->setIsDefault(true);
        $exercise = (new Exercise())
            ->setName('Panca piana')
            ->setSlug('panca-piana')
            ->setDescription('Test')
            ->setTrackingMode(ExerciseTrackingMode::WeightReps)
            ->setDefaultIncrementKg(2.5);

        $session = (new WorkoutSession())
            ->setAppUser($user)
            ->setSessionType(WorkoutSessionType::Calibration);

        $exerciseSession = (new ExerciseSession())->setExercise($exercise)->setPosition(1);
        $exerciseSession->addSetLog(
            (new SetLog())
                ->setSetNumber(1)
                ->setActualWeightKg(80)
                ->setActualReps(8)
                ->setRir(2)
        );
        $exerciseSession->addSetLog(
            (new SetLog())
                ->setSetNumber(2)
                ->setActualWeightKg(82.5)
                ->setActualReps(7)
                ->setRir(1)
        );
        $session->addExerciseSession($exerciseSession);

        $profile = (new LoadEstimationService())->buildInitialLoadProfile($user, $session);

        self::assertSame($exercise, $profile->getExercise());
        self::assertSame(LoadProfileConfidence::Medium, $profile->getConfidence());
        self::assertSame(4, $profile->getRanges()->count());
        self::assertStringContainsString('10-12', $profile->getRangeSummary());
        self::assertStringContainsString('4-6', $profile->getRangeSummary());
    }
}
