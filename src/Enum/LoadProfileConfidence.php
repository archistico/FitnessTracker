<?php

namespace App\Enum;

enum LoadProfileConfidence: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Bassa',
            self::Medium => 'Media',
            self::High => 'Alta',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'bg-orange-lt',
            self::Medium => 'bg-blue-lt',
            self::High => 'bg-green-lt',
        };
    }
}
