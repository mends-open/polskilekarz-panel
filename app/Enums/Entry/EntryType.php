<?php

namespace App\Enums\Entry;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum EntryType implements HasLabel
{
    // Operational (internal)
    case Task;
    case Appointment;
    case Note;
    case Warning;
    case Danger;

    // Clinical narratives
    case Interview;
    case Observation;
    case Condition;
    case Allergy;
    case Pregnancy;
    case Lactation;
    case Recommendation;
    case MedicalCertificate;
    case SickLeave;
    case CrossBorderPrescription;
    case Referral;
    case Attachment;

    public function getLabel(): ?string
    {
        return __(
            'enums.entry_type.' . Str::snake($this->name)
        );
    }
}
