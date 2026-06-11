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

    public function testEstimateOneRepMaxMarksHighRepEstimatesAsIndicative(): void
    {
        $calculator = new EstimatedStrengthCalculator();

        self::assertSame(150.0, $calculator->estimateOneRepMax(100.0, 15));
        self::assertSame(153.33, $calculator->estimateOneRepMax(100.0, 16));
        self::assertSame(EstimatedStrengthCalculator::RELIABILITY_STANDARD, $calculator->estimateOneRepMaxReliability(100.0, 15));
        self::assertSame(EstimatedStrengthCalculator::RELIABILITY_INDICATIVE, $calculator->estimateOneRepMaxReliability(100.0, 16));
        self::assertSame(EstimatedStrengthCalculator::RELIABILITY_STANDARD, $calculator->estimateOneRepMaxStatus(100.0, 15));
        self::assertSame(EstimatedStrengthCalculator::RELIABILITY_INDICATIVE, $calculator->estimateOneRepMaxStatus(100.0, 16));
        self::assertSame(16.0, $calculator->estimateOneRepMaxEffectiveReps(100.0, 16));
        self::assertSame('Stima indicativa: ripetizioni equivalenti alte.', $calculator->estimateOneRepMaxNotice(100.0, 16));
    }

    public function testEstimateOneRepMaxExcludesVeryHighRepSets(): void
    {
        $calculator = new EstimatedStrengthCalculator();

        self::assertSame(166.67, $calculator->estimateOneRepMax(100.0, 20));
        self::assertNull($calculator->estimateOneRepMax(100.0, 21));
        self::assertNull($calculator->estimateOneRepMaxReliability(100.0, 21));
        self::assertSame(EstimatedStrengthCalculator::ESTIMATE_STATUS_EXCLUDED, $calculator->estimateOneRepMaxStatus(100.0, 21));
        self::assertSame('Serie troppo lunga: non usata per il 1RM stimato.', $calculator->estimateOneRepMaxNotice(100.0, 21));
    }

    public function testEstimateOneRepMaxCapsRirContribution(): void
    {
        $calculator = new EstimatedStrengthCalculator();

        self::assertSame(150.0, $calculator->estimateOneRepMax(100.0, 10, 10.0));
        self::assertSame(15.0, $calculator->estimateOneRepMaxEffectiveReps(100.0, 10, 10.0));
    }
}
