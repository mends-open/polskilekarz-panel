<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Support\Currency\ResolvesCurrencyPrecision;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait InterpretsStripeAmounts
{
    use ResolvesCurrencyPrecision;

    protected function extractStripeAmount(?array $record, string $key): ?float
    {
        $value = Arr::get($record ?? [], $key);

        if (! is_numeric($value)) {
            return null;
        }

        $currency = $this->resolveStripeCurrency($record);

        return ((float) $value) / $this->resolveCurrencyMinorUnitDivisor($currency);
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
