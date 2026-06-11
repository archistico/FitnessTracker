<?php

namespace App\Enum;

enum ExerciseType: string
{
    case Strength = 'strength';
    case Hypertrophy = 'hypertrophy';
    case Machine = 'machine';
    case Bodyweight = 'bodyweight';
    case Cardio = 'cardio';
    case Accessory = 'accessory';
}
