<?php

namespace App\Enum;

enum WorkoutSessionStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Partial = 'partial';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Pianificata',
            self::InProgress => 'In corso',
            self::Completed => 'Completata',
            self::Partial => 'Parziale',
            self::Skipped => 'Saltata',
            self::Cancelled => 'Annullata',
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
            self::Cancelled => 'bg-red-lt',
        };
    }
}
