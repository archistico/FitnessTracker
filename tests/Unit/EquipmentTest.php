<?php

namespace App\Tests\Unit;

use App\Entity\Equipment;
use App\Entity\GymEquipment;
use App\Entity\GymProfile;
use App\Enum\EquipmentType;
use PHPUnit\Framework\TestCase;

final class EquipmentTest extends TestCase
{
    public function testMachineEquipmentCanBeMarkedAsAvailableInGym(): void
    {
        $equipment = (new Equipment())
            ->setName('Pulley')
            ->setSlug('pulley')
            ->setType(EquipmentType::Machine)
            ->setDescription('Macchina per tirate orizzontali.')
            ->setUsageInstructions('Mantenere schiena neutra e tirare la maniglia verso il corpo.')
            ->setIsMachine(true);

        $gym = (new GymProfile())->setName('Palestra principale');

        $gymEquipment = (new GymEquipment())
            ->setGymProfile($gym)
            ->setEquipment($equipment)
            ->setIsAvailable(true);

        self::assertSame(EquipmentType::Machine, $equipment->getType());
        self::assertTrue($equipment->isMachine());
        self::assertTrue($gymEquipment->isAvailable());
        self::assertSame($equipment, $gymEquipment->getEquipment());
    }

    public function testBodyweightIsNotMachine(): void
    {
        $equipment = (new Equipment())
            ->setName('Corpo libero')
            ->setSlug('corpo-libero')
            ->setType(EquipmentType::Bodyweight)
            ->setDescription('Esercizi senza carico esterno.')
            ->setIsMachine(false);

        self::assertSame(EquipmentType::Bodyweight, $equipment->getType());
        self::assertFalse($equipment->isMachine());
    }
}
