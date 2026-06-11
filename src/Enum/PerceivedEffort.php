<?php

namespace App\Enum;

enum PerceivedEffort: string
{
    case VeryEasy = 'very_easy';
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
    case VeryHard = 'very_hard';
    case Maximal = 'maximal';

    public function label(): string
    {
        return match ($this) {
            self::VeryEasy => 'Molto facile',
            self::Easy => 'Facile',
            self::Medium => 'Media',
            self::Hard => 'Dura',
            self::VeryHard => 'Molto dura',
            self::Maximal => 'Massimale',
        };
    }
}
