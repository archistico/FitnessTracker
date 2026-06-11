<?php

namespace App\Tests\Unit;

use App\Entity\Exercise;
use App\Entity\ExerciseSession;
use App\Entity\SetLog;
use App\Enum\ExerciseTrackingMode;
use App\Enum\PerceivedLoad;
use App\Enum\ProgressionType;
use App\Service\TreninoExerciseEvaluationService;
use PHPUnit\Framework\TestCase;

final class TreninoExerciseEvaluationServiceTest extends TestCase
{
    public function testAdvancesWhenAllSetsAreInsideRangeWithoutBlockingFatigue(): void
    {
        $exerciseSession = $this->buildTreninoExerciseSession(2);
        $exerciseSession
            ->addSetLog($this->buildSet(1, 6, 8, 8, 2.0))
            ->addSetLog($this->buildSet(2, 8, 10, 10, 2.0))
            ->addSetLog($this->buildSet(3, 8, 10, 9, 1.0))
            ->addSetLog($this->buildSet(4, 10, 12, 11, 1.0));

        $evaluation = (new TreninoExerciseEvaluationService())->evaluate($exerciseSession);

        self::assertNotNull($evaluation);
        self::assertTrue($evaluation->shouldAdvance());
        self::assertSame(2, $evaluation->getCurrentStep());
        self::assertSame(3, $evaluation->getNextStep());
    }

    public function testRepeatsWhenASetIsBelowTheRequiredRange(): void
    {
        $exerciseSession = $this->buildTreninoExerciseSession(3);
        $exerciseSession
            ->addSetLog($this->buildSet(1, 6, 8, 5, 0.0, PerceivedLoad::TooHeavy))
            ->addSetLog($this->buildSet(2, 6, 8, 7, 1.0))
            ->addSetLog($this->buildSet(3, 8, 10, 9, 1.0))
            ->addSetLog($this->buildSet(4, 8, 10, 8, 1.0));

        $evaluation = (new TreninoExerciseEvaluationService())->evaluate($exerciseSession);

        self::assertNotNull($evaluation);
        self::assertTrue($evaluation->shouldRepeat());
        self::assertSame(3, $evaluation->getCurrentStep());
        self::assertSame(3, $evaluation->getNextStep());
    }

    public function testStepSixCompletionRestartsNextCycleFromStepOne(): void
    {
        $exerciseSession = $this->buildTreninoExerciseSession(6);
        $exerciseSession
            ->addSetLog($this->buildSet(1, 4, 6, 6, 1.0))
            ->addSetLog($this->buildSet(2, 4, 6, 5, 1.0))
            ->addSetLog($this->buildSet(3, 6, 8, 8, 2.0))
            ->addSetLog($this->buildSet(4, 6, 8, 7, 1.0));

        $evaluation = (new TreninoExerciseEvaluationService())->evaluate($exerciseSession);

        self::assertNotNull($evaluation);
        self::assertTrue($evaluation->isCycleCompleted());
        self::assertSame(6, $evaluation->getCurrentStep());
        self::assertSame(1, $evaluation->getNextStep());
    }

    private function buildTreninoExerciseSession(int $step): ExerciseSession
    {
        $exercise = (new Exercise())
            ->setName('Panca piana')
            ->setSlug('panca-piana')
            ->setDescription('Test')
            ->setTrackingMode(ExerciseTrackingMode::WeightReps);

        return (new ExerciseSession())
            ->setExercise($exercise)
            ->setProgressionType(ProgressionType::TreninoInvictus)
            ->setProgressionStepNumber($step);
    }

    private function buildSet(int $setNumber, int $repMin, int $repMax, int $actualReps, float $rir, ?PerceivedLoad $perceivedLoad = null): SetLog
    {
        return (new SetLog())
            ->setSetNumber($setNumber)
            ->setTargetRepMin($repMin)
            ->setTargetRepMax($repMax)
            ->setActualReps($actualReps)
            ->setRir($rir)
            ->setPerceivedLoad($perceivedLoad);
    }
}
