<?php

namespace App\Service;

final class EstimatedStrengthCalculator
{
    public const RELIABILITY_STANDARD = 'standard';
    public const RELIABILITY_INDICATIVE = 'indicative';
    public const ESTIMATE_STATUS_EXCLUDED = 'excluded';


    private const MAX_RIR_FOR_ESTIMATE = 5.0;
    private const INDICATIVE_EFFECTIVE_REPS_THRESHOLD = 15.0;
    private const MAX_EFFECTIVE_REPS_FOR_ESTIMATE = 20.0;

    public function estimateOneRepMax(?float $weightKg, ?int $reps, ?float $rir = null): ?float
    {
        $effectiveReps = $this->calculateEffectiveReps($weightKg, $reps, $rir);
        if ($effectiveReps === null || $effectiveReps > self::MAX_EFFECTIVE_REPS_FOR_ESTIMATE) {
            return null;
        }

        return round($weightKg * (1 + ($effectiveReps / 30)), 2);
    }

    public function estimateOneRepMaxReliability(?float $weightKg, ?int $reps, ?float $rir = null): ?string
    {
        $status = $this->estimateOneRepMaxStatus($weightKg, $reps, $rir);
        if ($status === self::ESTIMATE_STATUS_EXCLUDED) {
            return null;
        }

        return $status;
    }

    public function estimateOneRepMaxStatus(?float $weightKg, ?int $reps, ?float $rir = null): ?string
    {
        $effectiveReps = $this->calculateEffectiveReps($weightKg, $reps, $rir);
        if ($effectiveReps === null) {
            return null;
        }

        if ($effectiveReps > self::MAX_EFFECTIVE_REPS_FOR_ESTIMATE) {
            return self::ESTIMATE_STATUS_EXCLUDED;
        }

        return $effectiveReps > self::INDICATIVE_EFFECTIVE_REPS_THRESHOLD
            ? self::RELIABILITY_INDICATIVE
            : self::RELIABILITY_STANDARD;
    }

    public function estimateOneRepMaxEffectiveReps(?float $weightKg, ?int $reps, ?float $rir = null): ?float
    {
        return $this->calculateEffectiveReps($weightKg, $reps, $rir);
    }

    public function estimateOneRepMaxNotice(?float $weightKg, ?int $reps, ?float $rir = null): ?string
    {
        $effectiveReps = $this->calculateEffectiveReps($weightKg, $reps, $rir);
        if ($effectiveReps === null) {
            return null;
        }

        if ($effectiveReps > self::MAX_EFFECTIVE_REPS_FOR_ESTIMATE) {
            return 'Serie troppo lunga: non usata per il 1RM stimato.';
        }

        if ($effectiveReps > self::INDICATIVE_EFFECTIVE_REPS_THRESHOLD) {
            return 'Stima indicativa: ripetizioni equivalenti alte.';
        }

        return null;
    }

    private function calculateEffectiveReps(?float $weightKg, ?int $reps, ?float $rir = null): ?float
    {
        if ($weightKg === null || $weightKg <= 0 || $reps === null || $reps <= 0) {
            return null;
        }

        $effectiveReps = (float) $reps;
        if ($rir !== null && $rir > 0) {
            $effectiveReps += min($rir, self::MAX_RIR_FOR_ESTIMATE);
        }

        return $effectiveReps;
    }
}
