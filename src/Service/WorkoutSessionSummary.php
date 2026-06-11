<?php

namespace App\Service;

use App\Entity\SetLog;
use App\Entity\WorkoutSession;

final class WorkoutSessionSummary
{
    /** @return array{plannedSets:int,completedSets:int,skippedSets:int,remainingSets:int,totalReps:int,totalWeightVolume:float,totalDurationSeconds:int,averageRir:?float,completionPercent:float} */
    public function summarize(WorkoutSession $session): array
    {
        $plannedSets = 0;
        $completedSets = 0;
        $skippedSets = 0;
        $totalReps = 0;
        $totalWeightVolume = 0.0;
        $totalDurationSeconds = 0;
        $rirSum = 0.0;
        $rirCount = 0;

        foreach ($session->getExerciseSessions() as $exerciseSession) {
            foreach ($exerciseSession->getSetLogs() as $setLog) {
                ++$plannedSets;

                if ($setLog->isSkipped()) {
                    ++$skippedSets;
                    continue;
                }

                if ($this->isCompletedSet($setLog)) {
                    ++$completedSets;
                }

                if ($setLog->getActualReps() !== null) {
                    $totalReps += $setLog->getActualReps();
                }

                if ($setLog->getActualWeightKg() !== null && $setLog->getActualReps() !== null) {
                    $totalWeightVolume += $setLog->getActualWeightKg() * $setLog->getActualReps();
                }

                if ($setLog->getActualDurationSeconds() !== null) {
                    $totalDurationSeconds += $setLog->getActualDurationSeconds();
                }

                if ($setLog->getRir() !== null) {
                    $rirSum += $setLog->getRir();
                    ++$rirCount;
                }
            }
        }

        $closedSets = $completedSets + $skippedSets;
        $remainingSets = max(0, $plannedSets - $closedSets);

        return [
            'plannedSets' => $plannedSets,
            'completedSets' => $completedSets,
            'skippedSets' => $skippedSets,
            'remainingSets' => $remainingSets,
            'totalReps' => $totalReps,
            'totalWeightVolume' => round($totalWeightVolume, 2),
            'totalDurationSeconds' => $totalDurationSeconds,
            'averageRir' => $rirCount > 0 ? round($rirSum / $rirCount, 2) : null,
            'completionPercent' => $plannedSets > 0 ? round(($closedSets / $plannedSets) * 100, 1) : 0.0,
        ];
    }

    private function isCompletedSet(SetLog $setLog): bool
    {
        return !$setLog->isSkipped() && $setLog->hasActualData();
    }
}
