<?php

namespace App\Tests\Unit;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutPlanExercise;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ProgressionType;
use App\Enum\WorkoutGoal;
use PHPUnit\Framework\TestCase;

final class WorkoutPlanTest extends TestCase
{
    public function testWorkoutPlanKeepsExercisesOrderedByPosition(): void
    {
        $user = (new AppUser())->setName('Utente demo');
        $plan = (new WorkoutPlan())
            ->setAppUser($user)
            ->setName('Lunedì - Gambe')
            ->setSlug('lunedi-gambe')
            ->setGoal(WorkoutGoal::StrengthHypertrophy)
            ->setSuggestedDayOfWeek(1);

        $first = (new WorkoutPlanExercise())
            ->setExercise($this->exercise('Cyclette', ExerciseTrackingMode::CardioMachine))
            ->setPosition(1)
            ->setProgressionType(ProgressionType::Cardio)
            ->setPlannedDurationSeconds(600);

        $second = (new WorkoutPlanExercise())
            ->setExercise($this->exercise('Squat', ExerciseTrackingMode::WeightReps))
            ->setPosition(2)
            ->setProgressionType(ProgressionType::TreninoInvictus)
            ->setPlannedSets(4)
            ->setPlannedRestSeconds(150);

        $plan->addExercise($first);
        $plan->addExercise($second);

        self::assertSame('Lunedì', $plan->getSuggestedDayLabel());
        self::assertCount(2, $plan->getExercises());
        self::assertSame(3, $plan->getNextPosition());
        self::assertSame($plan, $first->getWorkoutPlan());
        self::assertSame($plan, $second->getWorkoutPlan());
    }

    public function testInvalidSuggestedDayThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WorkoutPlan())->setSuggestedDayOfWeek(8);
    }

    private function exercise(string $name, ExerciseTrackingMode $trackingMode): Exercise
    {
        return (new Exercise())
            ->setName($name)
            ->setSlug(strtolower($name))
            ->setDescription($name)
            ->setTrackingMode($trackingMode);
    }
}
