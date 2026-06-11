<?php

namespace App\Service;

use App\Dto\TreninoExerciseEvaluation;
use App\Entity\ExerciseSession;
use App\Enum\ExerciseSessionStatus;
use App\Enum\PerceivedLoad;
use App\Enum\ProgressionType;

final class TreninoExerciseEvaluationService
{
    public function __construct(
        private readonly ?TreninoInvictusProgressionService $progressionService = null,
    ) {
    }

    public function evaluate(ExerciseSession $exerciseSession): ?TreninoExerciseEvaluation
    {
        if ($exerciseSession->getProgressionType() !== ProgressionType::TreninoInvictus) {
            return null;
        }

        $progressionService = $this->progressionService ?? new TreninoInvictusProgressionService();
        $currentStep = $progressionService->normalizeStepNumber($exerciseSession->getProgressionStepNumber());
        $reasons = [];
        $hasIncompleteSet = false;
        $hasSkippedSet = false;
        $hasBelowRangeSet = false;
        $hasTooHeavySet = false;
        $hasAnyRegisteredSet = false;

        if ($exerciseSession->getStatus() === ExerciseSessionStatus::Skipped) {
            return new TreninoExerciseEvaluation(
                $currentStep,
                $currentStep,
                'repeat',
                'Ripeti step',
                'bg-orange-lt',
                ['Esercizio saltato: lo step non può essere considerato consolidato.']
            );
        }

        foreach ($exerciseSession->getSetLogs() as $setLog) {
            if ($setLog->isSkipped()) {
                $hasSkippedSet = true;
                continue;
            }

            if (!$setLog->hasActualData() || $setLog->getActualReps() === null) {
                $hasIncompleteSet = true;
                continue;
            }

            $hasAnyRegisteredSet = true;
            $actualReps = $setLog->getActualReps();
            $targetMin = $setLog->getTargetRepMin();

            if ($targetMin !== null && $actualReps < $targetMin) {
                $hasBelowRangeSet = true;
            }

            $isAtLimit = $setLog->isReachedFailure()
                || ($setLog->getRir() !== null && $setLog->getRir() <= 0.0)
                || $setLog->getPerceivedLoad() === PerceivedLoad::TooHeavy
                || $setLog->getPerceivedLoad() === PerceivedLoad::Failure;

            if ($targetMin !== null && $actualReps <= $targetMin && $isAtLimit) {
                $hasTooHeavySet = true;
            }
        }

        if (!$hasAnyRegisteredSet) {
            return new TreninoExerciseEvaluation(
                $currentStep,
                $currentStep,
                'repeat',
                'Ripeti step',
                'bg-secondary-lt',
                ['Non ci sono serie registrate con ripetizioni: lo step rimane invariato.']
            );
        }

        if ($hasIncompleteSet) {
            $reasons[] = 'Sono presenti serie non registrate: chiudi o salta le serie prima di avanzare.';
        }

        if ($hasSkippedSet) {
            $reasons[] = 'Sono presenti serie saltate: lo step viene ripetuto per prudenza.';
        }

        if ($hasBelowRangeSet) {
            $reasons[] = 'Almeno una serie è sotto il minimo del range previsto.';
        }

        if ($hasTooHeavySet) {
            $reasons[] = 'Almeno una serie è stata al limite o troppo pesante già al minimo del range.';
        }

        if ($reasons !== []) {
            return new TreninoExerciseEvaluation(
                $currentStep,
                $currentStep,
                'repeat',
                'Ripeti step',
                'bg-yellow-lt',
                $reasons
            );
        }

        if ($currentStep >= 6) {
            return new TreninoExerciseEvaluation(
                $currentStep,
                1,
                'cycle_completed',
                'Ciclo completato',
                'bg-green-lt',
                ['Step 6 chiuso senza errori bloccanti: puoi iniziare un nuovo ciclo dal primo step.']
            );
        }

        return new TreninoExerciseEvaluation(
            $currentStep,
            $currentStep + 1,
            'advance',
            'Avanza step',
            'bg-green-lt',
            [sprintf('Step %d consolidato: il prossimo allenamento userà lo step %d.', $currentStep, $currentStep + 1)]
        );
    }
}
