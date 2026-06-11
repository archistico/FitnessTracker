<?php

namespace App\Enum;

enum LoadProfileSource: string
{
    case InitialCalibration = 'initial_calibration';
    case TrainingHistory = 'training_history';
    case ManualOverride = 'manual_override';

    public function label(): string
    {
        return match ($this) {
            self::InitialCalibration => 'Calibrazione iniziale',
            self::TrainingHistory => 'Storico allenamenti',
            self::ManualOverride => 'Correzione manuale',
        };
    }
}
