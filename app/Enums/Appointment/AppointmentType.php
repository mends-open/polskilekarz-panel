<?php

namespace App\Enums\Appointment;

use Filament\Support\Contracts\HasLabel;

enum AppointmentType: string implements HasLabel
{
    case General = 'general';
    case Psychiatric = 'psychiatric';
    case Psychological = 'psychological';
    case Prescription = 'prescription';
    case Documentation = 'documentation';

    public function getLabel(): ?string
    {
        return __('enums.appointment_type.' . $this->value);
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
