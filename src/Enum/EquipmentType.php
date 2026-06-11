<?php

namespace App\Enum;

enum EquipmentType: string
{
    case Machine = 'machine';
    case FreeWeight = 'free_weight';
    case Bodyweight = 'bodyweight';
    case Bench = 'bench';
    case Cable = 'cable';
    case Cardio = 'cardio';
    case Accessory = 'accessory';
}
