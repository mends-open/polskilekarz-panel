<?php

namespace App\Enums\Patient;

use Filament\Support\Contracts\HasLabel;

enum PatientGender: string implements HasLabel
{
    // http://hl7.org/fhir/administrative-gender
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case Unknown = 'unknown';

    public function getLabel(): ?string
    {
        return __('enums.patient_gender.' . $this->value);
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
