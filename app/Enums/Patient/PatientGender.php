<?php

namespace App\Enums\Patient;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum PatientGender implements HasLabel
{
    // http://hl7.org/fhir/administrative-gender
    case Male;
    case Female;
    case Other;
    case Unknown;

    public function getLabel(): ?string
    {
        return __(
            'enums.patient_gender.' . Str::snake($this->name)
        );
    }
}
