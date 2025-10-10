<?php

namespace App\Filament\Widgets\Stripe\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Open = 'open';
    case Paid = 'paid';
    case Uncollectible = 'uncollectible';
    case Void = 'void';

    public function getLabel(): ?string
    {
        $key = 'filament.widgets.stripe.enums.invoice_statuses.' . $this->value;

        $label = __($key);

        if ($label === $key) {
            return Str::headline($this->value);
        }

        return $label;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Paid => 'success',
            self::Open => 'info',
            self::Draft => 'secondary',
            self::Uncollectible => 'danger',
            self::Void => 'gray',
        };
    }

    public function getTotalBadgeColor(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Open, self::Draft, self::Uncollectible => 'danger',
            self::Void => 'gray',
        };
    }
}

