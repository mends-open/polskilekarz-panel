<?php

namespace App\Enums\Submission;

use Filament\Support\Contracts\HasLabel;

enum Type: int implements HasLabel
{
    case Unspecified = 0;
    case Registration = 1;
    case PrescriptionRequest = 2;

    public function getLabel(): ?string
    {
        return __('submission.type.'.$this->value);
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
