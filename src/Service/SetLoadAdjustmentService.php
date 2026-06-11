<?php

namespace App\Service;

use App\Dto\SetLoadAdjustment;
use App\Dto\SetWeightSuggestion;
use App\Entity\SetLog;
use App\Entity\WorkoutSession;
use App\Enum\PerceivedLoad;

final class SetLoadAdjustmentService
{
    /**
     * @return array<int, SetLoadAdjustment>
     */
    public function buildForSession(WorkoutSession $session): array
    {
        $result = [];

        foreach ($session->getExerciseSessions() as $exerciseSession) {
            foreach ($exerciseSession->getSetLogs() as $setLog) {
                if ($setLog->getId() !== null) {
                    $result[$setLog->getId()] = $this->evaluate($setLog);
                }
            }
        }

        return $result;
    }

    /**
     * @return array<int, SetWeightSuggestion>
     */
    public function buildWeightSuggestionsForSession(WorkoutSession $session): array
    {
        $result = [];

        foreach ($session->getExerciseSessions() as $exerciseSession) {
            foreach ($exerciseSession->getSetLogs() as $setLog) {
                if ($setLog->getId() !== null) {
                    $result[$setLog->getId()] = $this->buildWeightSuggestionForSet($setLog);
                }
            }
        }

        return $result;
    }

    public function buildWeightSuggestionForSet(SetLog $setLog): SetWeightSuggestion
    {
        if ($setLog->getTargetWeightKg() !== null) {
            return new SetWeightSuggestion(
                $setLog->getTargetWeightKg(),
                'Carico target',
                'Peso calcolato dalla calibrazione, dalla progressione o aggiornato automaticamente durante l’allenamento.'
            );
        }

        $previousCompatibleSet = $this->findPreviousClosedCompatibleSet($setLog);
        if ($previousCompatibleSet instanceof SetLog) {
            $adjustment = $this->evaluate($previousCompatibleSet);
            if ($adjustment->suggestedWeightKg !== null) {
                return new SetWeightSuggestion(
                    $adjustment->suggestedWeightKg,
                    'Dalla serie precedente',
                    sprintf(
                        'Questo peso deriva dalla correzione della serie %d dello stesso esercizio e dello stesso range.',
                        $previousCompatibleSet->getSetNumber()
                    )
                );
            }
        }

        return new SetWeightSuggestion(
            null,
            'Non disponibile',
            'Manca un profilo carichi, una progressione che lo calcoli o una serie precedente compatibile da cui ricavarlo.'
        );
    }

    public function evaluate(SetLog $setLog): SetLoadAdjustment
    {
        if ($setLog->isSkipped()) {
            return new SetLoadAdjustment(
                'not_evaluable',
                'Serie saltata',
                'bg-orange-lt',
                'La serie è stata saltata, quindi non viene usata per correggere il carico.'
            );
        }

        if ($setLog->getTargetRepMin() === null || $setLog->getTargetRepMax() === null) {
            return new SetLoadAdjustment(
                'not_evaluable',
                'Carico non valutabile',
                'bg-secondary-lt',
                'Questa serie non ha un range di ripetizioni target. La correzione automatica del carico verrà applicata agli esercizi con range reps.'
            );
        }

        if ($setLog->getActualReps() === null) {
            return new SetLoadAdjustment(
                'waiting',
                'In attesa dei dati',
                'bg-secondary-lt',
                'Inserisci almeno le ripetizioni effettive. Per suggerire il peso della prossima serie serve anche il peso usato.'
            );
        }

        if ($setLog->getActualWeightKg() === null) {
            return new SetLoadAdjustment(
                'missing_weight',
                'Peso mancante',
                'bg-yellow-lt',
                'Le ripetizioni sono registrate, ma manca il peso effettivo. Inseriscilo per ottenere una correzione utile.'
            );
        }

        $actualReps = $setLog->getActualReps();
        $actualWeightKg = $setLog->getActualWeightKg();
        $repMin = $setLog->getTargetRepMin();
        $repMax = $setLog->getTargetRepMax();
        $rir = $setLog->getRir();
        $perceivedLoad = $setLog->getPerceivedLoad();
        $incrementKg = max(0.5, $setLog->getExerciseSession()->getExercise()->getDefaultIncrementKg());

        if ($actualReps < $repMin) {
            return new SetLoadAdjustment(
                'reduce',
                'Riduci carico',
                'bg-red-lt',
                sprintf('Hai fatto %d ripetizioni, sotto il minimo previsto %d. Per la prossima serie conviene ridurre il peso.', $actualReps, $repMin),
                $this->roundToIncrement(max($incrementKg, $actualWeightKg - $incrementKg), $incrementKg)
            );
        }

        if ($setLog->isReachedFailure() || $perceivedLoad === PerceivedLoad::Failure || $perceivedLoad === PerceivedLoad::TooHeavy) {
            if ($actualReps <= $repMin || $perceivedLoad === PerceivedLoad::TooHeavy) {
                return new SetLoadAdjustment(
                    'reduce',
                    'Riduci carico',
                    'bg-red-lt',
                    'La serie è arrivata troppo vicina al limite per il range previsto. Riduci leggermente il peso nella prossima serie.',
                    $this->roundToIncrement(max($incrementKg, $actualWeightKg - $incrementKg), $incrementKg)
                );
            }

            return new SetLoadAdjustment(
                'hold_carefully',
                'Mantieni con prudenza',
                'bg-yellow-lt',
                'Sei dentro il range, ma la fatica è alta. Mantieni il peso solo se la tecnica resta pulita.',
                $this->roundToIncrement($actualWeightKg, $incrementKg)
            );
        }

        if ($rir !== null && $rir <= 0.0) {
            if ($actualReps <= $repMin) {
                return new SetLoadAdjustment(
                    'reduce',
                    'Riduci carico',
                    'bg-red-lt',
                    'Hai raggiunto il minimo del range ma senza ripetizioni in riserva. Riduci leggermente il carico.',
                    $this->roundToIncrement(max($incrementKg, $actualWeightKg - $incrementKg), $incrementKg)
                );
            }

            return new SetLoadAdjustment(
                'hold_carefully',
                'Mantieni con prudenza',
                'bg-yellow-lt',
                'Il range è rispettato, ma il margine è nullo. Mantieni il peso senza aumentare.',
                $this->roundToIncrement($actualWeightKg, $incrementKg)
            );
        }

        if ($actualReps >= $repMax) {
            if (($rir !== null && $rir >= 3.0) || $perceivedLoad === PerceivedLoad::TooLight) {
                return new SetLoadAdjustment(
                    'increase',
                    'Aumenta carico',
                    'bg-green-lt',
                    sprintf('Hai raggiunto il massimo del range %d-%d con margine. Puoi aumentare il peso nella prossima serie.', $repMin, $repMax),
                    $this->roundToIncrement($actualWeightKg + $incrementKg, $incrementKg)
                );
            }

            return new SetLoadAdjustment(
                'hold',
                'Mantieni carico',
                'bg-green-lt',
                'Hai raggiunto la parte alta del range con una fatica coerente. Mantieni il peso.',
                $this->roundToIncrement($actualWeightKg, $incrementKg)
            );
        }

        if (($rir !== null && $rir >= 4.0) || $perceivedLoad === PerceivedLoad::TooLight) {
            return new SetLoadAdjustment(
                'increase',
                'Aumenta carico',
                'bg-green-lt',
                'Sei dentro il range, ma con molto margine. Puoi aumentare leggermente il peso.',
                $this->roundToIncrement($actualWeightKg + $incrementKg, $incrementKg)
            );
        }

        if ($perceivedLoad === PerceivedLoad::HeavyButOk || ($rir !== null && $rir <= 1.0)) {
            return new SetLoadAdjustment(
                'hold_carefully',
                'Mantieni con prudenza',
                'bg-yellow-lt',
                'Il carico è produttivo ma già impegnativo. Ripeti lo stesso peso prima di aumentare.',
                $this->roundToIncrement($actualWeightKg, $incrementKg)
            );
        }

        return new SetLoadAdjustment(
            'hold',
            'Mantieni carico',
            'bg-blue-lt',
            'Serie dentro il range con margine adeguato. Mantieni questo peso per la prossima serie dello stesso blocco.',
            $this->roundToIncrement($actualWeightKg, $incrementKg)
        );
    }


    public function applySuggestedWeightToNextOpenCompatibleSet(SetLog $completedSetLog): ?SetLog
    {
        $adjustment = $this->evaluate($completedSetLog);

        if ($adjustment->suggestedWeightKg === null) {
            return null;
        }

        $nextSetLog = $this->findNextOpenCompatibleSet($completedSetLog);
        if (!$nextSetLog instanceof SetLog) {
            return null;
        }

        $nextSetLog->setTargetWeightKg($adjustment->suggestedWeightKg);

        return $nextSetLog;
    }

    private function findNextOpenCompatibleSet(SetLog $completedSetLog): ?SetLog
    {
        $setLogs = $completedSetLog->getExerciseSession()->getSetLogs()->toArray();
        usort($setLogs, static fn (SetLog $a, SetLog $b): int => $a->getSetNumber() <=> $b->getSetNumber());

        foreach ($setLogs as $candidate) {
            if ($candidate === $completedSetLog) {
                continue;
            }

            if ($candidate->getSetNumber() <= $completedSetLog->getSetNumber()) {
                continue;
            }

            if ($candidate->isSkipped() || $candidate->hasActualData()) {
                continue;
            }

            if (!$this->hasSameRepTarget($completedSetLog, $candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function findPreviousClosedCompatibleSet(SetLog $targetSetLog): ?SetLog
    {
        $setLogs = $targetSetLog->getExerciseSession()->getSetLogs()->toArray();
        usort($setLogs, static fn (SetLog $a, SetLog $b): int => $b->getSetNumber() <=> $a->getSetNumber());

        foreach ($setLogs as $candidate) {
            if ($candidate === $targetSetLog) {
                continue;
            }

            if ($candidate->getSetNumber() >= $targetSetLog->getSetNumber()) {
                continue;
            }

            if ($candidate->isSkipped() || !$candidate->hasActualData()) {
                continue;
            }

            if (!$this->hasSameRepTarget($candidate, $targetSetLog)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function hasSameRepTarget(SetLog $source, SetLog $candidate): bool
    {
        return $source->getTargetRepMin() === $candidate->getTargetRepMin()
            && $source->getTargetRepMax() === $candidate->getTargetRepMax();
    }

    private function roundToIncrement(float $weightKg, float $incrementKg): float
    {
        return round($weightKg / $incrementKg) * $incrementKg;
    }
}
