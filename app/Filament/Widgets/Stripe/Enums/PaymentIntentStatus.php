<?php

namespace App\Filament\Widgets\Stripe\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum PaymentIntentStatus: string implements HasColor, HasLabel
{
    case Canceled = 'canceled';
    case Processing = 'processing';
    case RequiresAction = 'requires_action';
    case RequiresCapture = 'requires_capture';
    case RequiresConfirmation = 'requires_confirmation';
    case RequiresPaymentMethod = 'requires_payment_method';
    case Succeeded = 'succeeded';

    public function getLabel(): ?string
    {
        $key = 'filament.widgets.stripe.enums.payment_intent_statuses.' . $this->value;

        $label = __($key);

        if ($label === $key) {
            return Str::headline($this->value);
        }

        return $label;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Succeeded => 'success',
            self::Processing => 'warning',
            self::RequiresAction,
            self::RequiresCapture,
            self::RequiresConfirmation,
            self::RequiresPaymentMethod => 'danger',
            self::Canceled => 'gray',
        };
    }

    public function getAmountBadgeColor(): string
    {
        return match ($this) {
            self::Succeeded => 'success',
            default => 'gray',
        };
    }
}

