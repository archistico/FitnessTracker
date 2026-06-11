<?php

namespace App\Tests\Unit;

use App\DataFixtures\FitnessCatalog;
use PHPUnit\Framework\TestCase;

final class FitnessCatalogTest extends TestCase
{
    public function testCatalogContainsUploadedEquipmentAndExercises(): void
    {
        self::assertCount(90, FitnessCatalog::equipmentSeed());
        self::assertCount(100, FitnessCatalog::exerciseSeed());
    }

    public function testCatalogSlugsAreUniqueInsideEachCatalog(): void
    {
        $equipmentSlugs = array_map(static fn (array $item): string => $item['slug'], FitnessCatalog::equipmentSeed());
        $exerciseSlugs = array_map(static fn (array $item): string => $item['slug'], FitnessCatalog::exerciseSeed());

        self::assertSame($equipmentSlugs, array_values(array_unique($equipmentSlugs)));
        self::assertSame($exerciseSlugs, array_values(array_unique($exerciseSlugs)));
    }

    public function testExerciseDefaultEquipmentReferencesKnownCatalogOrLegacyEquipment(): void
    {
        $knownEquipmentSlugs = array_map(static fn (array $item): string => $item['slug'], FitnessCatalog::equipmentSeed());
        $knownEquipmentSlugs = array_merge($knownEquipmentSlugs, [
            'bilanciere',
            'cavi',
            'corpo-libero',
            'gradino-box',
            'lat-machine',
            'leg-extension',
            'manubri',
            'panca-piana',
            'pressa',
            'pulley',
            'rack-supporti-squat',
            'vogatore',
        ]);

        foreach (FitnessCatalog::exerciseSeed() as $exercise) {
            if ($exercise['equipment'] === null) {
                continue;
            }

            self::assertContains($exercise['equipment'], $knownEquipmentSlugs, sprintf('Unknown equipment slug for exercise "%s".', $exercise['name']));
        }
    }
}
