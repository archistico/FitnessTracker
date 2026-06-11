<?php

namespace App\Enum;

enum ProgressionType: string
{
    case None = 'none';
    case Fixed = 'fixed';
    case TreninoInvictus = 'trenino_invictus';
    case Manual = 'manual';
    case TimeBased = 'time_based';
    case Cardio = 'cardio';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nessuna',
            self::Fixed => 'Serie fisse',
            self::TreninoInvictus => 'Trenino Invictus',
            self::Manual => 'Manuale',
            self::TimeBased => 'A tempo',
            self::Cardio => 'Cardio',
        };
    }
}
