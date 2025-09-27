<?php

namespace App\Enums\Entry;

use Filament\Support\Contracts\HasLabel;

enum Type: int implements HasLabel
{
    case Unspecified = 0;
    // Operational (internal)
    case Task = 1;
    case Note = 2;
    case Warning = 3;
    case Danger = 4;

    // Clinical narratives
    case Interview = 5;
    case Observation = 6;
    case Condition = 7;
    case Allergy = 8;
    case Pregnancy = 9;
    case Lactation = 10;
    case Recommendation = 11;
    case MedicalCertificate = 12;
    case SickLeave = 13;
    case PsychologicalAssessment = 14;
    case CrossBorderPrescription = 15;
    case Referral = 16;
    case Attachment = 17;

    public function getLabel(): ?string
    {
        return __('entry.type.'.$this->value);
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
