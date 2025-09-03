<?php

namespace App\Enums\Submission;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum SubmissionType implements HasLabel
{
    case Registration;
    case PreVisit;
    case PostVisit;

    public function getLabel(): ?string
    {
        return __(
            'enums.submission_type.' . Str::snake($this->name)
        );
    }
}
