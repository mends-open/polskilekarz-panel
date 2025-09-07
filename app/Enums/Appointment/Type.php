<?php

namespace App\Enums\Appointment;

use Filament\Support\Contracts\HasLabel;

enum Type: string implements HasLabel
{
    case Unspecified = 'unspecified';
    case PrimaryCare = 'primary_care';
    case Psychiatric = 'psychiatric';
    case Psychological = 'psychological';
    case Prescription = 'prescription';
    case Documentation = 'documentation';

    public function getLabel(): ?string
    {
        return __('appointments.type.' . $this->value);
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
