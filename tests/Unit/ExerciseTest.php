<?php

namespace App\Tests\Unit;

use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Enum\EquipmentType;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ExerciseType;
use PHPUnit\Framework\TestCase;

final class ExerciseTest extends TestCase
{
    public function testExerciseStoresTrackingModeAndEquipment(): void
    {
        $equipment = (new Equipment())
            ->setName('Lat machine')
            ->setSlug('lat-machine')
            ->setType(EquipmentType::Machine)
            ->setDescription('Macchina per tirate verticali.')
            ->setIsMachine(true);

        $exercise = (new Exercise())
            ->setName('Lat machine avanti al petto')
            ->setSlug('lat-machine-avanti-petto')
            ->setDescription('Tirata verticale per dorsali.')
            ->setExecutionInstructions('Tirare la sbarra verso il petto controllando la risalita.')
            ->setPrimaryMuscles(['dorsali'])
            ->setSecondaryMuscles(['bicipiti', 'deltoide posteriore'])
            ->setTrackingMode(ExerciseTrackingMode::WeightReps)
            ->setExerciseType(ExerciseType::Machine)
            ->setDefaultEquipment($equipment)
            ->setDefaultIncrementKg(5.0)
            ->setIsFundamental(false);

        self::assertSame('Lat machine avanti al petto', $exercise->getName());
        self::assertSame(ExerciseTrackingMode::WeightReps, $exercise->getTrackingMode());
        self::assertSame(ExerciseType::Machine, $exercise->getExerciseType());
        self::assertSame($equipment, $exercise->getDefaultEquipment());
        self::assertSame(['dorsali'], $exercise->getPrimaryMuscles());
        self::assertSame(['bicipiti', 'deltoide posteriore'], $exercise->getSecondaryMuscles());
        self::assertFalse($exercise->isFundamental());
    }

    public function testCardioExerciseCanAvoidWeightBasedTracking(): void
    {
        $exercise = (new Exercise())
            ->setName('Cyclette')
            ->setSlug('cyclette')
            ->setDescription('Cardio a tempo e intensità.')
            ->setTrackingMode(ExerciseTrackingMode::CardioMachine)
            ->setExerciseType(ExerciseType::Cardio)
            ->setDefaultIncrementKg(0.0);

        self::assertSame(ExerciseTrackingMode::CardioMachine, $exercise->getTrackingMode());
        self::assertSame(ExerciseType::Cardio, $exercise->getExerciseType());
        self::assertSame(0.0, $exercise->getDefaultIncrementKg());
    }
}
