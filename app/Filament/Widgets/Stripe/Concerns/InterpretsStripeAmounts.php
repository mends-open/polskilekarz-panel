<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait InterpretsStripeAmounts
{
    protected function extractStripeAmount(?array $record, string $key): ?float
    {
        $value = Arr::get($record ?? [], $key);

        if (! is_numeric($value)) {
            return null;
        }

        return ((float) $value) / 100;
    }

    protected function resolveStripeCurrency(?array $record, ?string $fallback = 'usd'): ?string
    {
        $currency = Arr::get($record ?? [], 'currency');

        if (is_string($currency) && $currency !== '') {
            return Str::lower($currency);
        }

        return $fallback !== null ? Str::lower($fallback) : null;
    }
}
