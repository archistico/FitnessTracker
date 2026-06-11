<?php

namespace App\Tests\Unit;

use App\Enum\ExerciseTrackingMode;
use PHPUnit\Framework\TestCase;

final class ExerciseTrackingModeTest extends TestCase
{
    public function testWeightRepsUsesStrengthFieldsAsPrimary(): void
    {
        $mode = ExerciseTrackingMode::WeightReps;

        self::assertTrue($mode->usesWeight());
        self::assertTrue($mode->usesReps());
        self::assertFalse($mode->usesDuration());
        self::assertTrue($mode->usesRirByDefault());
        self::assertTrue($mode->usesPerceivedLoadByDefault());
        self::assertTrue($mode->usesPerceivedEffortByDefault());
    }

    public function testCardioMachineUsesTimeDistanceAndLevelAsPrimary(): void
    {
        $mode = ExerciseTrackingMode::CardioMachine;

        self::assertFalse($mode->usesWeight());
        self::assertFalse($mode->usesReps());
        self::assertTrue($mode->usesDuration());
        self::assertTrue($mode->usesDistance());
        self::assertTrue($mode->usesResistanceLevel());
        self::assertFalse($mode->usesRirByDefault());
        self::assertFalse($mode->usesPerceivedLoadByDefault());
        self::assertTrue($mode->usesPerceivedEffortByDefault());
    }

    public function testFreeNotesKeepsTechnicalFieldsAsExtraData(): void
    {
        $mode = ExerciseTrackingMode::FreeNotes;

        self::assertFalse($mode->usesWeight());
        self::assertFalse($mode->usesReps());
        self::assertFalse($mode->usesDuration());
        self::assertFalse($mode->usesDistance());
        self::assertFalse($mode->usesResistanceLevel());
        self::assertFalse($mode->usesRirByDefault());
        self::assertFalse($mode->usesPerceivedLoadByDefault());
        self::assertFalse($mode->usesPerceivedEffortByDefault());
    }
}
