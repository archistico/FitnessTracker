<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\ExerciseLoadProfile;
use App\Entity\ExerciseLoadRange;
use App\Entity\SetLog;
use App\Entity\WorkoutSession;
use App\Enum\LoadProfileConfidence;
use App\Enum\LoadProfileSource;
use App\Enum\WorkoutSessionType;

final class LoadEstimationService
{
    /** @return array{oneRepMax: float, usedRir: bool} */
    public function estimateOneRepMaxFromSet(SetLog $setLog): array
    {
        $weight = $setLog->getActualWeightKg();
        $reps = $setLog->getActualReps();

        if ($weight === null || $weight <= 0 || $reps === null || $reps <= 0) {
            throw new \InvalidArgumentException('Per stimare il carico servono almeno kg e reps reali.');
        }

        $usedRir = $setLog->getRir() !== null;
        $rir = $setLog->getRir() ?? 2.0;
        $theoreticalReps = $reps + $rir;

        return [
            'oneRepMax' => $weight * (1 + ($theoreticalReps / 30)),
            'usedRir' => $usedRir,
        ];
    }

    public function estimateWeightForTargetReps(float $estimatedOneRepMaxKg, int $targetReps): float
    {
        if ($estimatedOneRepMaxKg <= 0 || $targetReps < 1) {
            throw new \InvalidArgumentException('Parametri non validi per la stima del peso.');
        }

        return $estimatedOneRepMaxKg / (1 + ($targetReps / 30));
    }

    public function roundToIncrement(float $weightKg, float $incrementKg): float
    {
        $safeIncrement = $incrementKg > 0 ? $incrementKg : 2.5;

        return round($weightKg / $safeIncrement) * $safeIncrement;
    }

    public function buildInitialLoadProfile(AppUser $appUser, WorkoutSession $session): ExerciseLoadProfile
    {
        if ($session->getSessionType() !== WorkoutSessionType::Calibration) {
            throw new \InvalidArgumentException('Il profilo carichi iniziale può essere creato solo da una sessione di calibrazione.');
        }

        $exerciseSession = $session->getExerciseSessions()->first();
        if ($exerciseSession === false) {
            throw new \InvalidArgumentException('La sessione di calibrazione non contiene esercizi.');
        }

        $exercise = $exerciseSession->getExercise();
        $bestOneRepMax = null;
        $hasRir = false;
        $validSets = 0;

        foreach ($exerciseSession->getSetLogs() as $setLog) {
            if ($setLog->isSkipped() || $setLog->getActualWeightKg() === null || $setLog->getActualReps() === null) {
                continue;
            }

            $estimate = $this->estimateOneRepMaxFromSet($setLog);
            $bestOneRepMax = $bestOneRepMax === null ? $estimate['oneRepMax'] : max($bestOneRepMax, $estimate['oneRepMax']);
            $hasRir = $hasRir || $estimate['usedRir'];
            ++$validSets;
        }

        if ($bestOneRepMax === null) {
            throw new \InvalidArgumentException('Inserisci almeno una serie con kg e reps prima di finalizzare la calibrazione.');
        }

        $profile = new ExerciseLoadProfile();
        $profile
            ->setAppUser($appUser)
            ->setExercise($exercise)
            ->setSource(LoadProfileSource::InitialCalibration)
            ->setConfidence($this->resolveConfidence($validSets, $hasRir))
            ->setEstimatedOneRepMaxKg($bestOneRepMax);

        foreach ($this->buildRanges($exercise, $bestOneRepMax) as [$repMin, $repMax, $weight]) {
            $profile->addRange(new ExerciseLoadRange($repMin, $repMax, $weight));
        }

        return $profile;
    }

    /** @return list<array{0:int, 1:int, 2:float}> */
    private function buildRanges(Exercise $exercise, float $estimatedOneRepMaxKg): array
    {
        $conservativeFactor = 0.975;
        $increment = $exercise->getDefaultIncrementKg();

        $ranges = [
            [10, 12, 11],
            [8, 10, 9],
            [6, 8, 7],
            [4, 6, 5],
        ];

        $result = [];
        foreach ($ranges as [$repMin, $repMax, $targetReps]) {
            $estimated = $this->estimateWeightForTargetReps($estimatedOneRepMaxKg, $targetReps) * $conservativeFactor;
            $result[] = [$repMin, $repMax, $this->roundToIncrement($estimated, $increment)];
        }

        return $result;
    }

    private function resolveConfidence(int $validSets, bool $hasRir): LoadProfileConfidence
    {
        if ($validSets >= 3 && $hasRir) {
            return LoadProfileConfidence::High;
        }

        if ($validSets >= 2 || $hasRir) {
            return LoadProfileConfidence::Medium;
        }

        return LoadProfileConfidence::Low;
    }
}
