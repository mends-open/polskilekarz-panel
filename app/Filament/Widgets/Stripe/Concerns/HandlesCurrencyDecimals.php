<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Support\Stripe\Currency;

trait HandlesCurrencyDecimals
{
    protected function isZeroDecimal(string $currency): bool
    {
        return Currency::isZeroDecimal($currency);
    }

    protected function currencyDecimalPlaces(string $currency): int
    {
        return Currency::decimalPlaces($currency);
    }

    protected function currencyDivisor(string $currency): int
    {
        return Currency::divisor($currency);
    }

    protected function normalizeStripeAmount(int|float $amount, string $currency): float
    {
        return Currency::toFloat($amount, $currency);
    }

    protected function convertToStripeAmount(float $amount, string $currency): int
    {
        return Currency::fromFloat($amount, $currency);
    }

    protected function formatCurrencyForDisplay(int $amount, string $currency, string $decimalSeparator = '.', string $thousandsSeparator = ' '): string
    {
        return Currency::format($amount, $currency, $decimalSeparator, $thousandsSeparator);
    }
}
