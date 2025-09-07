<?php

namespace App\Enums\Submission;

use Filament\Support\Contracts\HasLabel;

enum Type: string implements HasLabel
{
    case Unspecified = 'unspecified';
    case Registration = 'registration';
    case PrescriptionRequest = 'prescription_request';

    public function getLabel(): ?string
    {
        return __('submission.type.' . $this->value);
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
