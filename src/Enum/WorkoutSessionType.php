<?php

namespace App\Enum;

enum WorkoutSessionType: string
{
    case Training = 'training';
    case Calibration = 'calibration';
    case Test = 'test';
    case Deload = 'deload';
    case Free = 'free';

    public function label(): string
    {
        return match ($this) {
            self::Training => 'Allenamento',
            self::Calibration => 'Calibrazione',
            self::Test => 'Test',
            self::Deload => 'Scarico',
            self::Free => 'Libero',
        };
    }
}
