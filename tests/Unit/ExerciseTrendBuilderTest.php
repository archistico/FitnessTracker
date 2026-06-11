<?php

namespace App\Tests\Unit;

use App\Service\ExerciseTrendBuilder;
use PHPUnit\Framework\TestCase;

final class ExerciseTrendBuilderTest extends TestCase
{
    public function testBuildKeepsLatestPointsInChronologicalOrder(): void
    {
        $builder = new ExerciseTrendBuilder();

        $trend = $builder->build([
            $this->sessionSummary('2026-06-10', 3, 75.0, 2400.0),
            $this->sessionSummary('2026-06-03', 2, 72.5, 2300.0),
            $this->sessionSummary('2026-05-27', 1, 70.0, 2100.0),
        ], 2);

        self::assertTrue($trend['hasEnoughData']);
        self::assertCount(2, $trend['points']);
        self::assertSame('03/06', $trend['points'][0]['dateLabel']);
        self::assertSame('10/06', $trend['points'][1]['dateLabel']);
        self::assertSame(97, $trend['points'][0]['bestWeightPercent']);
        self::assertSame(100, $trend['points'][1]['bestWeightPercent']);
        self::assertSame('+2,5 kg rispetto alla sessione precedente.', $trend['bestWeightChange']['label']);
        self::assertSame('+100 kg rispetto alla sessione precedente.', $trend['volumeChange']['label']);
    }

    public function testBuildReportsMissingTrendWithSingleSession(): void
    {
        $builder = new ExerciseTrendBuilder();

        $trend = $builder->build([
            $this->sessionSummary('2026-06-10', 1, 70.0, 2100.0),
        ]);

        self::assertFalse($trend['hasEnoughData']);
        self::assertCount(1, $trend['points']);
        self::assertSame('Servono almeno due sessioni con questo dato.', $trend['bestWeightChange']['label']);
    }

    /** @return array{date:\DateTimeImmutable,sessionId:?int,planName:string,sessionType:string,setCount:int,totalReps:int,totalVolume:float,bestWeightKg:?float,averageRir:?float} */
    private function sessionSummary(string $date, int $sessionId, ?float $bestWeightKg, float $totalVolume): array
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
            'averageRir' => 2.0,
        ];
    }
}
