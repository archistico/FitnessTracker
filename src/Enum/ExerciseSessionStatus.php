<?php

namespace App\Enum;

enum ExerciseSessionStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Partial = 'partial';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Pianificato',
            self::InProgress => 'In corso',
            self::Completed => 'Completato',
            self::Partial => 'Parziale',
            self::Skipped => 'Saltato',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Planned => 'bg-secondary-lt',
            self::InProgress => 'bg-blue-lt',
            self::Completed => 'bg-green-lt',
            self::Partial => 'bg-yellow-lt',
            self::Skipped => 'bg-orange-lt',
        };
    }
}
