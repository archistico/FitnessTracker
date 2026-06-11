<?php

namespace App\Tests\Unit;

use App\Entity\Exercise;
use App\Entity\ExerciseSession;
use App\Entity\SetLog;
use App\Enum\PerceivedLoad;
use App\Service\SetLoadAdjustmentService;
use PHPUnit\Framework\TestCase;

final class SetLoadAdjustmentServiceTest extends TestCase
{
    public function testSuggestsIncreaseWhenTopRangeHasLargeMargin(): void
    {
        $setLog = $this->buildSet(8, 10, 10, 80.0, 3.0);

        $adjustment = (new SetLoadAdjustmentService())->evaluate($setLog);

        self::assertSame('increase', $adjustment->status);
        self::assertSame('Aumenta carico', $adjustment->label);
        self::assertSame(82.5, $adjustment->suggestedWeightKg);
    }

    public function testSuggestsReductionWhenBelowRange(): void
    {
        $setLog = $this->buildSet(8, 10, 6, 80.0, 0.0, PerceivedLoad::TooHeavy);

        $adjustment = (new SetLoadAdjustmentService())->evaluate($setLog);

        self::assertSame('reduce', $adjustment->status);
        self::assertSame('Riduci carico', $adjustment->label);
        self::assertSame(77.5, $adjustment->suggestedWeightKg);
    }

    public function testKeepsWeightWhenInsideRangeWithUsefulMargin(): void
    {
        $setLog = $this->buildSet(8, 10, 9, 80.0, 2.0, PerceivedLoad::Correct);

        $adjustment = (new SetLoadAdjustmentService())->evaluate($setLog);

        self::assertSame('hold', $adjustment->status);
        self::assertSame('Mantieni carico', $adjustment->label);
        self::assertSame(80.0, $adjustment->suggestedWeightKg);
    }

    public function testReportsMissingWeightWhenOnlyRepsAreInserted(): void
    {
        $setLog = $this->buildSet(8, 10, 9, null, 2.0);

        $adjustment = (new SetLoadAdjustmentService())->evaluate($setLog);

        self::assertSame('missing_weight', $adjustment->status);
        self::assertNull($adjustment->suggestedWeightKg);
    }

    public function testAppliesSuggestedWeightToNextOpenCompatibleSet(): void
    {
        $exerciseSession = $this->buildExerciseSession();
        $completedSet = $this->buildSet(8, 10, 10, 80.0, 3.0)->setSetNumber(1);
        $nextCompatibleSet = (new SetLog())
            ->setSetNumber(2)
            ->setTargetRepMin(8)
            ->setTargetRepMax(10);
        $differentRangeSet = (new SetLog())
            ->setSetNumber(3)
            ->setTargetRepMin(10)
            ->setTargetRepMax(12);

        $exerciseSession
            ->addSetLog($completedSet)
            ->addSetLog($nextCompatibleSet)
            ->addSetLog($differentRangeSet);

        $updatedSet = (new SetLoadAdjustmentService())->applySuggestedWeightToNextOpenCompatibleSet($completedSet);

        self::assertSame($nextCompatibleSet, $updatedSet);
        self::assertSame(82.5, $nextCompatibleSet->getTargetWeightKg());
        self::assertNull($differentRangeSet->getTargetWeightKg());
    }

    public function testDoesNotOverwriteClosedNextSet(): void
    {
        $exerciseSession = $this->buildExerciseSession();
        $completedSet = $this->buildSet(8, 10, 10, 80.0, 3.0)->setSetNumber(1);
        $alreadyClosedSet = (new SetLog())
            ->setSetNumber(2)
            ->setTargetRepMin(8)
            ->setTargetRepMax(10)
            ->setActualReps(8)
            ->setActualWeightKg(80.0);
        $nextOpenSet = (new SetLog())
            ->setSetNumber(3)
            ->setTargetRepMin(8)
            ->setTargetRepMax(10);

        $exerciseSession
            ->addSetLog($completedSet)
            ->addSetLog($alreadyClosedSet)
            ->addSetLog($nextOpenSet);

        $updatedSet = (new SetLoadAdjustmentService())->applySuggestedWeightToNextOpenCompatibleSet($completedSet);

        self::assertSame($nextOpenSet, $updatedSet);
        self::assertSame(82.5, $nextOpenSet->getTargetWeightKg());
    }


    public function testBuildsDisplaySuggestionFromPreviousCompatibleSetWhenTargetWeightIsMissing(): void
    {
        $exerciseSession = $this->buildExerciseSession();
        $completedSet = $this->buildSet(8, 10, 10, 60.0, 3.0)->setSetNumber(1);
        $nextCompatibleSet = (new SetLog())
            ->setSetNumber(2)
            ->setTargetRepMin(8)
            ->setTargetRepMax(10);

        $exerciseSession
            ->addSetLog($completedSet)
            ->addSetLog($nextCompatibleSet);

        $suggestion = (new SetLoadAdjustmentService())->buildWeightSuggestionForSet($nextCompatibleSet);

        self::assertTrue($suggestion->hasWeight());
        self::assertSame(62.5, $suggestion->weightKg);
        self::assertSame('Dalla serie precedente', $suggestion->sourceLabel);
    }

    public function testTargetWeightHasPriorityOverPreviousSetSuggestion(): void
    {
        $exerciseSession = $this->buildExerciseSession();
        $completedSet = $this->buildSet(8, 10, 10, 60.0, 3.0)->setSetNumber(1);
        $nextCompatibleSet = (new SetLog())
            ->setSetNumber(2)
            ->setTargetRepMin(8)
            ->setTargetRepMax(10)
            ->setTargetWeightKg(65.0);

        $exerciseSession
            ->addSetLog($completedSet)
            ->addSetLog($nextCompatibleSet);

        $suggestion = (new SetLoadAdjustmentService())->buildWeightSuggestionForSet($nextCompatibleSet);

        self::assertTrue($suggestion->hasWeight());
        self::assertSame(65.0, $suggestion->weightKg);
        self::assertSame('Carico target', $suggestion->sourceLabel);
    }

    private function buildSet(
        int $targetRepMin,
        int $targetRepMax,
        int $actualReps,
        ?float $actualWeightKg,
        ?float $rir,
        ?PerceivedLoad $perceivedLoad = null,
    ): SetLog {
        return (new SetLog())
            ->setExerciseSession($this->buildExerciseSession())
            ->setTargetRepMin($targetRepMin)
            ->setTargetRepMax($targetRepMax)
            ->setActualReps($actualReps)
            ->setActualWeightKg($actualWeightKg)
            ->setRir($rir)
            ->setPerceivedLoad($perceivedLoad);
    }

    private function buildExerciseSession(): ExerciseSession
    {
        $exercise = (new Exercise())
            ->setName('Panca piana')
            ->setSlug('panca-piana')
            ->setDescription('Test')
            ->setDefaultIncrementKg(2.5);

        return (new ExerciseSession())->setExercise($exercise);
    }
}
