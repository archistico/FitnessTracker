<?php

namespace App\Enum;

enum PerceivedLoad: string
{
    case TooLight = 'too_light';
    case Correct = 'correct';
    case HeavyButOk = 'heavy_but_ok';
    case TooHeavy = 'too_heavy';
    case Failure = 'failure';

    /**
     * Cedimento resta nell'enum per compatibilità con dati già salvati,
     * ma non viene più proposto nei form: ora il cedimento è espresso da RIR = 0.
     *
     * @return list<self>
     */
    public static function selectableCases(): array
    {
        return [
            self::TooLight,
            self::Correct,
            self::HeavyButOk,
            self::TooHeavy,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::TooLight => 'Troppo leggero',
            self::Correct => 'Corretto',
            self::HeavyButOk => 'Pesante ma gestibile',
            self::TooHeavy => 'Troppo pesante',
            self::Failure => 'Cedimento',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TooLight => 'bg-cyan-lt',
            self::Correct => 'bg-green-lt',
            self::HeavyButOk => 'bg-yellow-lt',
            self::TooHeavy => 'bg-orange-lt',
            self::Failure => 'bg-red-lt',
        };
    }
}
