<?php

namespace App\Service;

final class EstimatedStrengthCalculator
{
    private const MAX_RIR_FOR_ESTIMATE = 5.0;
    private const MAX_EFFECTIVE_REPS_FOR_ESTIMATE = 30.0;

    public function estimateOneRepMax(?float $weightKg, ?int $reps, ?float $rir = null): ?float
    {
        if ($weightKg === null || $weightKg <= 0 || $reps === null || $reps <= 0) {
            return null;
        }

        $effectiveReps = (float) $reps;
        if ($rir !== null && $rir > 0) {
            $effectiveReps += min($rir, self::MAX_RIR_FOR_ESTIMATE);
        }

        $effectiveReps = min($effectiveReps, self::MAX_EFFECTIVE_REPS_FOR_ESTIMATE);

        return round($weightKg * (1 + ($effectiveReps / 30)), 2);
    }
}
