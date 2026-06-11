<?php

namespace App\Dto;

final class TreninoExerciseEvaluation
{
    /** @param list<string> $reasons */
    public function __construct(
        private readonly int $currentStep,
        private readonly int $nextStep,
        private readonly string $decision,
        private readonly string $label,
        private readonly string $badgeClass,
        private readonly array $reasons,
    ) {
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function getNextStep(): int
    {
        return $this->nextStep;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getBadgeClass(): string
    {
        return $this->badgeClass;
    }

    /** @return list<string> */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    public function shouldAdvance(): bool
    {
        return $this->decision === 'advance';
    }

    public function shouldRepeat(): bool
    {
        return $this->decision === 'repeat';
    }

    public function isCycleCompleted(): bool
    {
        return $this->decision === 'cycle_completed';
    }
}
