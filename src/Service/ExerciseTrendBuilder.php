<?php

namespace App\Service;

final class ExerciseTrendBuilder
{
    /**
     * @param list<array{date:\DateTimeImmutable,sessionId:?int,planName:string,sessionType:string,setCount:int,totalReps:int,totalVolume:float,bestWeightKg:?float,bestEstimatedStrengthKg:?float,bestEstimatedReliability:?string,averageRir:?float}> $sessionSummaries
     * @return array{points:list<array{date:\DateTimeImmutable,dateLabel:string,sessionId:?int,planName:string,sessionType:string,setCount:int,totalReps:int,totalVolume:float,bestWeightKg:?float,bestEstimatedStrengthKg:?float,bestEstimatedReliability:?string,averageRir:?float,bestWeightPercent:int,volumePercent:int,bestEstimatedPercent:int,rirPercent:int}>,hasEnoughData:bool,bestWeightChange:array{label:string,class:string},volumeChange:array{label:string,class:string},bestEstimatedChange:array{label:string,class:string}}
     */
    public function build(array $sessionSummaries, int $maxPoints = 8): array
    {
        if ($maxPoints < 2) {
            $maxPoints = 2;
        }

        $recentNewestFirst = array_slice($sessionSummaries, 0, $maxPoints);
        $points = array_values(array_reverse($recentNewestFirst));

        $normalizedPoints = array_map(static function (array $session): array {
            return [
                'date' => $session['date'],
                'dateLabel' => $session['date']->format('d/m'),
                'sessionId' => $session['sessionId'],
                'planName' => $session['planName'],
                'sessionType' => $session['sessionType'],
                'setCount' => $session['setCount'],
                'totalReps' => $session['totalReps'],
                'totalVolume' => $session['totalVolume'],
                'bestWeightKg' => $session['bestWeightKg'],
                'bestEstimatedStrengthKg' => $session['bestEstimatedStrengthKg'],
                'bestEstimatedReliability' => $session['bestEstimatedReliability'] ?? null,
                'averageRir' => $session['averageRir'],
                'bestWeightPercent' => 0,
                'volumePercent' => 0,
                'bestEstimatedPercent' => 0,
                'rirPercent' => 0,
            ];
        }, $points);

        $maxWeight = $this->maxNullable($normalizedPoints, 'bestWeightKg');
        $maxVolume = $this->maxNullable($normalizedPoints, 'totalVolume');
        $maxEstimatedStrength = $this->maxNullable($normalizedPoints, 'bestEstimatedStrengthKg');
        $maxRir = $this->maxNullable($normalizedPoints, 'averageRir');

        foreach ($normalizedPoints as &$point) {
            $point['bestWeightPercent'] = $this->percent($point['bestWeightKg'], $maxWeight);
            $point['volumePercent'] = $this->percent($point['totalVolume'], $maxVolume);
            $point['bestEstimatedPercent'] = $this->percent($point['bestEstimatedStrengthKg'], $maxEstimatedStrength);
            $point['rirPercent'] = $this->percent($point['averageRir'], $maxRir);
        }
        unset($point);

        $previous = count($normalizedPoints) >= 2 ? $normalizedPoints[count($normalizedPoints) - 2] : null;
        $latest = $normalizedPoints !== [] ? $normalizedPoints[count($normalizedPoints) - 1] : null;

        return [
            'points' => $normalizedPoints,
            'hasEnoughData' => count($normalizedPoints) >= 2,
            'bestWeightChange' => $this->describeChange($previous['bestWeightKg'] ?? null, $latest['bestWeightKg'] ?? null, 'kg', 1),
            'volumeChange' => $this->describeChange($previous['totalVolume'] ?? null, $latest['totalVolume'] ?? null, 'kg', 0),
            'bestEstimatedChange' => $this->describeChange($previous['bestEstimatedStrengthKg'] ?? null, $latest['bestEstimatedStrengthKg'] ?? null, 'kg 1RM stimato', 1),
        ];
    }

    /**
     * @param list<array<string,mixed>> $points
     */
    private function maxNullable(array $points, string $key): ?float
    {
        $max = null;
        foreach ($points as $point) {
            if (!array_key_exists($key, $point) || $point[$key] === null) {
                continue;
            }

            $value = (float) $point[$key];
            $max = $max === null ? $value : max($max, $value);
        }

        return $max;
    }

    private function percent(mixed $value, ?float $max): int
    {
        if ($value === null || $max === null || $max <= 0) {
            return 0;
        }

        return max(8, min(100, (int) round(((float) $value / $max) * 100)));
    }

    /** @return array{label:string,class:string} */
    private function describeChange(?float $previous, ?float $latest, string $unit, int $decimals): array
    {
        if ($previous === null || $latest === null) {
            return [
                'label' => 'Servono almeno due sessioni con questo dato.',
                'class' => 'text-secondary',
            ];
        }

        $threshold = $decimals === 0 ? 1.0 : 0.1;
        $change = $latest - $previous;
        if (abs($change) < $threshold) {
            return [
                'label' => 'Stabile rispetto alla sessione precedente.',
                'class' => 'text-secondary',
            ];
        }

        $formatted = number_format(abs($change), $decimals, ',', '.');

        return [
            'label' => sprintf('%s%s %s rispetto alla sessione precedente.', $change > 0 ? '+' : '-', $formatted, $unit),
            'class' => $change > 0 ? 'text-success' : 'text-warning',
        ];
    }
}
