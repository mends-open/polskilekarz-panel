<?php

namespace App\Enums\Appointment;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum AppointmentType implements HasLabel
{
    case General;
    case Psychiatric;
    case Psychological;
    case Prescription;
    case Documentation;

    public function getLabel(): ?string
    {
        return __(
            'enums.appointment_type.' . Str::snake($this->name)
        );
    }

    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->name] = $case->getLabel();
        }

        return $labels;
    }
}
