<?php

namespace App\Tests\Unit;

use App\Service\EstimatedStrengthCalculator;
use PHPUnit\Framework\TestCase;

final class EstimatedStrengthCalculatorTest extends TestCase
{
    public function testEstimateOneRepMaxUsesEpleyFormulaWithoutRir(): void
    {
        $calculator = new EstimatedStrengthCalculator();

        self::assertSame(116.67, $calculator->estimateOneRepMax(100.0, 5));
    }

    public function testEstimateOneRepMaxUsesRirAsPotentialReps(): void
    {
        $calculator = new EstimatedStrengthCalculator();

        self::assertSame(123.33, $calculator->estimateOneRepMax(100.0, 5, 2.0));
    }

    public function testEstimateOneRepMaxIgnoresIncompleteSets(): void
    {
        $calculator = new EstimatedStrengthCalculator();

        self::assertNull($calculator->estimateOneRepMax(null, 8, 1.0));
        self::assertNull($calculator->estimateOneRepMax(80.0, null, 1.0));
        self::assertNull($calculator->estimateOneRepMax(0.0, 8, 1.0));
        self::assertNull($calculator->estimateOneRepMax(80.0, 0, 1.0));
    }
}
