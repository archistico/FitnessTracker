<?php

namespace App\Tests\Unit;

use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Service\AvailableExerciseFilter;
use PHPUnit\Framework\TestCase;

final class AvailableExerciseFilterTest extends TestCase
{
    public function testItKeepsAllExercisesWhenAvailableOnlyIsFalse(): void
    {
        $latMachine = (new Equipment())->setName('Lat machine')->setSlug('lat-machine');
        $exercise = (new Exercise())->setName('Lat machine avanti')->setDefaultEquipment($latMachine);

        $result = (new AvailableExerciseFilter())->filter([$exercise], [], false);

        self::assertSame([$exercise], $result);
    }

    public function testItKeepsOnlyExercisesWithAvailableEquipment(): void
    {
        $latMachine = (new Equipment())->setName('Lat machine')->setSlug('lat-machine');
        $pulley = (new Equipment())->setName('Pulley')->setSlug('pulley');

        $latExercise = (new Exercise())->setName('Lat machine avanti')->setDefaultEquipment($latMachine);
        $pulleyExercise = (new Exercise())->setName('Pulley basso')->setDefaultEquipment($pulley);

        $result = (new AvailableExerciseFilter())->filter([$latExercise, $pulleyExercise], ['pulley'], true);

        self::assertSame([$pulleyExercise], $result);
    }

    public function testItKeepsExercisesWithoutEquipmentAsAvailable(): void
    {
        $exercise = (new Exercise())->setName('Addominali a terra')->setDefaultEquipment(null);

        $result = (new AvailableExerciseFilter())->filter([$exercise], [], true);

        self::assertSame([$exercise], $result);
    }
}
