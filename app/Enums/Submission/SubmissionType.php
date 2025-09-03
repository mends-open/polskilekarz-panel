<?php

namespace App\Enums\Submission;

use Filament\Support\Contracts\HasLabel;

enum SubmissionType: string implements HasLabel
{
    case Registration = 'registration';
    case PreVisit = 'pre_visit';
    case PostVisit = 'post_visit';

    public function getLabel(): ?string
    {
        return __('enums.submission_type.' . $this->value);
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
