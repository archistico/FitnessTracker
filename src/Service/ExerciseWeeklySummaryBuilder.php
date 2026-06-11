<?php

namespace App\Service;

final class ExerciseWeeklySummaryBuilder
{
    /**
     * @param list<array{date:\DateTimeImmutable,sessionId:?int,planName:string,sessionType:string,setCount:int,totalReps:int,totalVolume:float,bestWeightKg:?float,bestEstimatedStrengthKg:?float,bestEstimatedReliability:?string,averageRir:?float}> $sessionSummaries
     * @return array{weeks:list<array{weekKey:string,label:string,rangeLabel:string,sessionCount:int,setCount:int,totalReps:int,totalVolume:float,bestWeightKg:?float,bestEstimatedStrengthKg:?float,bestEstimatedReliability:?string,averageRir:?float,volumePercent:int,bestEstimatedPercent:int}>,hasData:bool,hasMultipleWeeks:bool}
     */
    public function build(array $sessionSummaries, int $maxWeeks = 8): array
    {
        if ($maxWeeks < 1) {
            $maxWeeks = 1;
        }

        $weeksByKey = [];

        foreach ($sessionSummaries as $session) {
            $date = $session['date'];
            $weekStart = $date
                ->setISODate((int) $date->format('o'), (int) $date->format('W'), 1)
                ->setTime(0, 0);
            $weekEnd = $weekStart->modify('+6 days');
            $weekKey = sprintf('%s-W%s', $weekStart->format('o'), $weekStart->format('W'));

            if (!isset($weeksByKey[$weekKey])) {
                $weeksByKey[$weekKey] = [
                    'weekKey' => $weekKey,
                    'label' => sprintf('Settimana %s/%s', $weekStart->format('W'), $weekStart->format('o')),
                    'rangeLabel' => sprintf('%s - %s', $weekStart->format('d/m'), $weekEnd->format('d/m')),
                    'weekStart' => $weekStart,
                    'sessionCount' => 0,
                    'setCount' => 0,
                    'totalReps' => 0,
                    'totalVolume' => 0.0,
                    'bestWeightKg' => null,
                    'bestEstimatedStrengthKg' => null,
                    'bestEstimatedReliability' => null,
                    'rirWeightedSum' => 0.0,
                    'rirWeight' => 0,
                    'averageRir' => null,
                    'volumePercent' => 0,
                    'bestEstimatedPercent' => 0,
                ];
            }

            $weeksByKey[$weekKey]['sessionCount']++;
            $weeksByKey[$weekKey]['setCount'] += $session['setCount'];
            $weeksByKey[$weekKey]['totalReps'] += $session['totalReps'];
            $weeksByKey[$weekKey]['totalVolume'] += $session['totalVolume'];

            if ($session['bestWeightKg'] !== null) {
                $weeksByKey[$weekKey]['bestWeightKg'] = max($weeksByKey[$weekKey]['bestWeightKg'] ?? 0, $session['bestWeightKg']);
            }

            if ($session['bestEstimatedStrengthKg'] !== null) {
                if ($weeksByKey[$weekKey]['bestEstimatedStrengthKg'] === null || $session['bestEstimatedStrengthKg'] > $weeksByKey[$weekKey]['bestEstimatedStrengthKg']) {
                    $weeksByKey[$weekKey]['bestEstimatedStrengthKg'] = $session['bestEstimatedStrengthKg'];
                    $weeksByKey[$weekKey]['bestEstimatedReliability'] = $session['bestEstimatedReliability'] ?? null;
                }
            }

            if ($session['averageRir'] !== null && $session['setCount'] > 0) {
                $weeksByKey[$weekKey]['rirWeightedSum'] += $session['averageRir'] * $session['setCount'];
                $weeksByKey[$weekKey]['rirWeight'] += $session['setCount'];
            }
        }

        $weeks = array_values($weeksByKey);
        foreach ($weeks as &$week) {
            $week['averageRir'] = $week['rirWeight'] > 0 ? $week['rirWeightedSum'] / $week['rirWeight'] : null;
        }
        unset($week);

        usort($weeks, static function (array $a, array $b): int {
            return $b['weekStart']->getTimestamp() <=> $a['weekStart']->getTimestamp();
        });

        $weeks = array_slice($weeks, 0, $maxWeeks);
        $weeks = array_values(array_reverse($weeks));

        $maxVolume = $this->maxNullable($weeks, 'totalVolume');
        $maxEstimatedStrength = $this->maxNullable($weeks, 'bestEstimatedStrengthKg');

        foreach ($weeks as &$week) {
            $week['volumePercent'] = $this->percent($week['totalVolume'], $maxVolume);
            $week['bestEstimatedPercent'] = $this->percent($week['bestEstimatedStrengthKg'], $maxEstimatedStrength);
            unset($week['weekStart'], $week['rirWeightedSum'], $week['rirWeight']);
        }
        unset($week);

        return [
            'weeks' => $weeks,
            'hasData' => $weeks !== [],
            'hasMultipleWeeks' => count($weeks) >= 2,
        ];
    }

    /** @param list<array<string,mixed>> $items */
    private function maxNullable(array $items, string $key): ?float
    {
        $max = null;
        foreach ($items as $item) {
            if (!array_key_exists($key, $item) || $item[$key] === null) {
                continue;
            }

            $value = (float) $item[$key];
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
}
