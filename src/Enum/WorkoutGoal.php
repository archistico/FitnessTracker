<?php

namespace App\Enum;

enum WorkoutGoal: string
{
    case General = 'general';
    case Strength = 'strength';
    case Hypertrophy = 'hypertrophy';
    case StrengthHypertrophy = 'strength_hypertrophy';
    case Conditioning = 'conditioning';
    case Calibration = 'calibration';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Generale',
            self::Strength => 'Forza',
            self::Hypertrophy => 'Ipertrofia',
            self::StrengthHypertrophy => 'Forza + ipertrofia',
            self::Conditioning => 'Condizionamento',
            self::Calibration => 'Calibrazione',
        };
    }
}
