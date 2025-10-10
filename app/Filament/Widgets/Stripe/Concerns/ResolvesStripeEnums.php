<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Filament\Widgets\Stripe\Enums\InvoiceStatus;
use App\Filament\Widgets\Stripe\Enums\PaymentIntentStatus;

trait ResolvesStripeEnums
{
    protected function makeInvoiceStatus(?string $status): ?InvoiceStatus
    {
        if (! is_string($status) || $status === '') {
            return null;
        }

        return InvoiceStatus::tryFrom($status);
    }

    protected function makePaymentIntentStatus(?string $status): ?PaymentIntentStatus
    {
        if (! is_string($status) || $status === '') {
            return null;
        }

        return PaymentIntentStatus::tryFrom($status);
    }
}

