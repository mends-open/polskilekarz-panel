<?php

namespace App\Enums\Medication;

use Illuminate\Support\Str;

enum Country: int
{
    case Unspecified = 0;
    case EuropeanUnion = 1;
    case Austria = 2;
    case Belgium = 3;
    case Bulgaria = 4;
    case Croatia = 5;
    case Cyprus = 6;
    case CzechRepublic = 7;
    case Denmark = 8;
    case Estonia = 9;
    case Finland = 10;
    case France = 11;
    case Germany = 12;
    case Greece = 13;
    case Hungary = 14;
    case Iceland = 15;
    case Ireland = 16;
    case Italy = 17;
    case Latvia = 18;
    case Liechtenstein = 19;
    case Lithuania = 20;
    case Luxembourg = 21;
    case Malta = 22;
    case Netherlands = 23;
    case Norway = 24;
    case Poland = 25;
    case Portugal = 26;
    case Romania = 27;
    case Slovakia = 28;
    case Slovenia = 29;
    case Spain = 30;
    case Sweden = 31;
    case UnitedKingdomNorthernIreland = 32;

    public static function tryFromName(string $name): ?self
    {
        $caseName = Str::of($name)
            ->replace(['(', ')', '-', ',', '/', '.'], ' ')
            ->squish()
            ->studly()
            ->toString();

        foreach (self::cases() as $case) {
            if ($case->name === $caseName) {
                return $case;
            }
        }

        return null;
    }
}
