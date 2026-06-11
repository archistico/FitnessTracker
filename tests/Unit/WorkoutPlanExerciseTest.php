<?php

namespace App\Tests\Unit;

use App\Entity\Exercise;
use App\Entity\WorkoutPlanExercise;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ProgressionType;
use PHPUnit\Framework\TestCase;

final class WorkoutPlanExerciseTest extends TestCase
{
    public function testWeightRepsPrescriptionSummary(): void
    {
        $item = (new WorkoutPlanExercise())
            ->setExercise($this->exercise(ExerciseTrackingMode::WeightReps))
            ->setProgressionType(ProgressionType::Fixed)
            ->setPlannedSets(3)
            ->setPlannedRepMin(8)
            ->setPlannedRepMax(10)
            ->setPlannedRestSeconds(150);

        self::assertSame('3x8-10 reps', $item->getPrescriptionSummary());
        self::assertSame('2:30', $item->getRestSummary());
    }

    public function testCardioPrescriptionSummaryUsesDuration(): void
    {
        $item = (new WorkoutPlanExercise())
            ->setExercise($this->exercise(ExerciseTrackingMode::CardioMachine))
            ->setProgressionType(ProgressionType::Cardio)
            ->setPlannedDurationSeconds(600);

        self::assertSame('10 min', $item->getPrescriptionSummary());
    }

    public function testPositionMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WorkoutPlanExercise())->setPosition(0);
    }

    private function exercise(ExerciseTrackingMode $trackingMode): Exercise
    {
        return (new Exercise())
            ->setName('Test')
            ->setSlug('test')
            ->setDescription('Test')
            ->setTrackingMode($trackingMode);
    }
}
