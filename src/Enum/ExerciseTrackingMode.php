<?php

namespace App\Enum;

enum ExerciseTrackingMode: string
{
    case WeightReps = 'weight_reps';
    case BodyweightReps = 'bodyweight_reps';
    case RepsOnly = 'reps_only';
    case Time = 'time';
    case TimeDistance = 'time_distance';
    case CardioMachine = 'cardio_machine';
    case IsometricTime = 'isometric_time';
    case FreeNotes = 'free_notes';

    public function label(): string
    {
        return match ($this) {
            self::WeightReps => 'Peso + reps',
            self::BodyweightReps => 'Corpo libero + reps',
            self::RepsOnly => 'Solo reps',
            self::Time => 'Tempo',
            self::TimeDistance => 'Tempo + distanza',
            self::CardioMachine => 'Cardio macchina',
            self::IsometricTime => 'Isometrico a tempo',
            self::FreeNotes => 'Note libere',
        };
    }

    public function usesWeight(): bool
    {
        return $this === self::WeightReps;
    }

    public function usesReps(): bool
    {
        return in_array($this, [self::WeightReps, self::BodyweightReps, self::RepsOnly], true);
    }

    public function usesDuration(): bool
    {
        return in_array($this, [self::Time, self::TimeDistance, self::CardioMachine, self::IsometricTime], true);
    }

    public function usesDistance(): bool
    {
        return in_array($this, [self::TimeDistance, self::CardioMachine], true);
    }

    public function usesResistanceLevel(): bool
    {
        return $this === self::CardioMachine;
    }

    public function usesRirByDefault(): bool
    {
        return in_array($this, [self::WeightReps, self::BodyweightReps, self::RepsOnly], true);
    }

    public function usesPerceivedLoadByDefault(): bool
    {
        return in_array($this, [self::WeightReps, self::BodyweightReps, self::RepsOnly], true);
    }

    public function usesPerceivedEffortByDefault(): bool
    {
        return $this !== self::FreeNotes;
    }

    public function usesFailureByDefault(): bool
    {
        return in_array($this, [self::WeightReps, self::BodyweightReps, self::RepsOnly, self::IsometricTime], true);
    }
}
