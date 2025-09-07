<?php

namespace App\Enums\Entry;

use Filament\Support\Contracts\HasLabel;

enum Type: string implements HasLabel
{
    case Unspecified = 'unspecified';
    // Operational (internal)
    case Task = 'task';
    case Appointment = 'appointment';
    case Note = 'note';
    case Warning = 'warning';
    case Danger = 'danger';

    // Clinical narratives
    case Interview = 'interview';
    case Observation = 'observation';
    case Condition = 'condition';
    case Allergy = 'allergy';
    case Pregnancy = 'pregnancy';
    case Lactation = 'lactation';
    case Recommendation = 'recommendation';
    case MedicalCertificate = 'medical_certificate';
    case SickLeave = 'sick_leave';
    case PsychologicalAssessment = 'psychological_assessment';
    case CrossBorderPrescription = 'cross_border_prescription';
    case Referral = 'referral';
    case Attachment = 'attachment';

    public function getLabel(): ?string
    {
        return __('entry.type.' . $this->value);
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
