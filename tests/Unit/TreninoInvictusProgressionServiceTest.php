<?php

namespace App\Tests\Unit;

use App\Service\TreninoInvictusProgressionService;
use PHPUnit\Framework\TestCase;

final class TreninoInvictusProgressionServiceTest extends TestCase
{
    public function testStepOneCreatesTwoEightTenAndTwoTenTwelveSets(): void
    {
        $service = new TreninoInvictusProgressionService();

        $blocks = $service->getStepBlocks(1);

        self::assertCount(2, $blocks);
        self::assertSame(['repMin' => 8, 'repMax' => 10, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Metabolico-ipertrofico'], $blocks[0]);
        self::assertSame(['repMin' => 10, 'repMax' => 12, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Metabolico'], $blocks[1]);
    }

    public function testStepSixCreatesTwoHeavyBlocks(): void
    {
        $service = new TreninoInvictusProgressionService();

        $blocks = $service->getStepBlocks(6);

        self::assertCount(2, $blocks);
        self::assertSame(4, $blocks[0]['repMin']);
        self::assertSame(6, $blocks[0]['repMax']);
        self::assertSame(2, $blocks[0]['setCount']);
        self::assertSame(6, $blocks[1]['repMin']);
        self::assertSame(8, $blocks[1]['repMax']);
        self::assertSame(2, $blocks[1]['setCount']);
    }

    public function testStepNumberIsNormalized(): void
    {
        $service = new TreninoInvictusProgressionService();

        self::assertSame(1, $service->normalizeStepNumber(null));
        self::assertSame(1, $service->normalizeStepNumber(0));
        self::assertSame(6, $service->normalizeStepNumber(99));
    }
}
