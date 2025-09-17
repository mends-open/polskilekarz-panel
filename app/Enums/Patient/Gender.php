<?php

namespace App\Enums\Patient;

use Filament\Support\Contracts\HasLabel;

enum Gender: int implements HasLabel
{
    // http://hl7.org/fhir/administrative-gender
    case Male = 0;
    case Female = 1;
    case Other = 2;
    case Unknown = 3;

    public function getLabel(): ?string
    {
        return __('patient.gender.'.$this->value);
    }

    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->getLabel();
        }

        return $labels;
    }
}
