<?php

namespace App\Tests\Unit;

use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Enum\EquipmentType;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ExerciseType;
use App\Service\CatalogListFilter;
use PHPUnit\Framework\TestCase;

final class CatalogListFilterTest extends TestCase
{
    public function testItFiltersEquipmentBySearchTypeAndKind(): void
    {
        $latMachine = (new Equipment())
            ->setName('Lat machine')
            ->setSlug('lat-machine')
            ->setType(EquipmentType::Machine)
            ->setDescription('Trazioni verticali guidate')
            ->setIsMachine(true);

        $dumbbells = (new Equipment())
            ->setName('Manubri')
            ->setSlug('manubri')
            ->setType(EquipmentType::FreeWeight)
            ->setDescription('Pesi liberi')
            ->setIsMachine(false);

        $result = (new CatalogListFilter())->filterEquipment([$latMachine, $dumbbells], [
            'q' => 'trazioni',
            'type' => EquipmentType::Machine->value,
            'kind' => 'machine',
        ]);

        self::assertSame([$latMachine], $result);
    }

    public function testItFiltersExercisesByMuscleEquipmentTrackingAndAvailability(): void
    {
        $latMachine = (new Equipment())
            ->setName('Lat machine')
            ->setSlug('lat-machine')
            ->setType(EquipmentType::Machine)
            ->setDescription('Trazioni verticali')
            ->setIsMachine(true);

        $bench = (new Equipment())
            ->setName('Panca piana')
            ->setSlug('panca-piana')
            ->setType(EquipmentType::Bench)
            ->setDescription('Supporto orizzontale')
            ->setIsMachine(false);

        $latPulldown = (new Exercise())
            ->setName('Lat machine')
            ->setSlug('lat-machine-esercizio')
            ->setDescription('Trazione verticale')
            ->setPrimaryMuscles(['Dorsali'])
            ->setSecondaryMuscles(['Bicipiti'])
            ->setTrackingMode(ExerciseTrackingMode::WeightReps)
            ->setExerciseType(ExerciseType::Machine)
            ->setDefaultEquipment($latMachine)
            ->setIsFundamental(true);

        $benchPress = (new Exercise())
            ->setName('Panca piana')
            ->setSlug('panca-piana-bilanciere')
            ->setDescription('Spinta orizzontale')
            ->setPrimaryMuscles(['Pettorali'])
            ->setSecondaryMuscles(['Tricipiti'])
            ->setTrackingMode(ExerciseTrackingMode::WeightReps)
            ->setExerciseType(ExerciseType::Strength)
            ->setDefaultEquipment($bench)
            ->setIsFundamental(true);

        $result = (new CatalogListFilter())->filterExercises([$latPulldown, $benchPress], ['lat-machine'], [
            'q' => 'verticale',
            'availableOnly' => true,
            'trackingMode' => ExerciseTrackingMode::WeightReps->value,
            'exerciseType' => ExerciseType::Machine->value,
            'muscle' => 'Dorsali',
            'equipmentSlug' => 'lat-machine',
            'fundamental' => 'yes',
        ]);

        self::assertSame([$latPulldown], $result);
    }

    public function testItCollectsDistinctMuscleOptions(): void
    {
        $first = (new Exercise())
            ->setName('Panca')
            ->setPrimaryMuscles(['Pettorali'])
            ->setSecondaryMuscles(['Tricipiti']);

        $second = (new Exercise())
            ->setName('Push down')
            ->setPrimaryMuscles(['Tricipiti'])
            ->setSecondaryMuscles(['Pettorali']);

        $result = (new CatalogListFilter())->collectMuscleOptions([$first, $second]);

        self::assertSame(['Pettorali', 'Tricipiti'], $result);
    }
}
