<?php

namespace App\Tests\Unit;

use App\Service\ExerciseWeeklySummaryBuilder;
use PHPUnit\Framework\TestCase;

final class ExerciseWeeklySummaryBuilderTest extends TestCase
{
    public function testBuildAggregatesSessionsByIsoWeek(): void
    {
        $builder = new ExerciseWeeklySummaryBuilder();

        $trend = $builder->build([
            $this->sessionSummary('2026-06-10', 2, 75.0, 100.0, 2600.0, 3.0),
            $this->sessionSummary('2026-06-08', 1, 72.5, 98.0, 2400.0, 1.0),
            $this->sessionSummary('2026-06-03', 3, 70.0, 94.0, 2100.0, 2.0),
        ]);

        self::assertTrue($trend['hasData']);
        self::assertTrue($trend['hasMultipleWeeks']);
        self::assertCount(2, $trend['weeks']);

        self::assertSame('2026-W23', $trend['weeks'][0]['weekKey']);
        self::assertSame('Settimana 23/2026', $trend['weeks'][0]['label']);
        self::assertSame(1, $trend['weeks'][0]['sessionCount']);
        self::assertSame(4, $trend['weeks'][0]['setCount']);
        self::assertSame(2100.0, $trend['weeks'][0]['totalVolume']);
        self::assertSame(42, $trend['weeks'][0]['volumePercent']);

        self::assertSame('2026-W24', $trend['weeks'][1]['weekKey']);
        self::assertSame(2, $trend['weeks'][1]['sessionCount']);
        self::assertSame(8, $trend['weeks'][1]['setCount']);
        self::assertSame(64, $trend['weeks'][1]['totalReps']);
        self::assertSame(5000.0, $trend['weeks'][1]['totalVolume']);
        self::assertSame(75.0, $trend['weeks'][1]['bestWeightKg']);
        self::assertSame(100.0, $trend['weeks'][1]['bestEstimatedStrengthKg']);
        self::assertSame(2.0, $trend['weeks'][1]['averageRir']);
        self::assertSame(100, $trend['weeks'][1]['volumePercent']);
        self::assertSame(100, $trend['weeks'][1]['bestEstimatedPercent']);
    }

    public function testBuildKeepsLatestWeeksInChronologicalOrder(): void
    {
        $builder = new ExerciseWeeklySummaryBuilder();

        $trend = $builder->build([
            $this->sessionSummary('2026-06-17', 4, 80.0, 106.0, 2700.0, 1.5),
            $this->sessionSummary('2026-06-10', 2, 75.0, 100.0, 2600.0, 2.0),
            $this->sessionSummary('2026-06-03', 1, 70.0, 94.0, 2100.0, 2.5),
        ], 2);

        self::assertCount(2, $trend['weeks']);
        self::assertSame('2026-W24', $trend['weeks'][0]['weekKey']);
        self::assertSame('2026-W25', $trend['weeks'][1]['weekKey']);
    }

    public function testBuildReportsNoDataWithoutSessions(): void
    {
        $builder = new ExerciseWeeklySummaryBuilder();

        $trend = $builder->build([]);

        self::assertFalse($trend['hasData']);
        self::assertFalse($trend['hasMultipleWeeks']);
        self::assertSame([], $trend['weeks']);
    }

    /** @return array{date:\DateTimeImmutable,sessionId:?int,planName:string,sessionType:string,setCount:int,totalReps:int,totalVolume:float,bestWeightKg:?float,bestEstimatedStrengthKg:?float,bestEstimatedReliability:?string,averageRir:?float} */
    private function sessionSummary(string $date, int $sessionId, ?float $bestWeightKg, ?float $bestEstimatedStrengthKg, float $totalVolume, ?float $averageRir): array
    {
        return [
            'date' => new \DateTimeImmutable($date),
            'sessionId' => $sessionId,
            'planName' => 'Scheda test',
            'sessionType' => 'Standard',
            'setCount' => 4,
            'totalReps' => 32,
            'totalVolume' => $totalVolume,
            'bestWeightKg' => $bestWeightKg,
            'bestEstimatedStrengthKg' => $bestEstimatedStrengthKg,
            'bestEstimatedReliability' => null,
            'averageRir' => $averageRir,
        ];
    }
}
