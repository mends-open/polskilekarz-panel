<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use Illuminate\Support\Str;

trait HandlesCurrencyDecimals
{
    /**
     * List of zero-decimal currencies as defined by Stripe.
     *
     * @link https://stripe.com/docs/currencies#zero-decimal
     */
    protected const array ZERO_DECIMAL_CURRENCIES = [
        'bif',
        'clp',
        'djf',
        'gnf',
        'jpy',
        'kmf',
        'krw',
        'mga',
        'pyg',
        'rwf',
        'ugx',
        'vnd',
        'vuv',
        'xaf',
        'xof',
        'xpf',
    ];

    protected function isZeroDecimal(string $currency): bool
    {
        return in_array(Str::lower($currency), self::ZERO_DECIMAL_CURRENCIES, true);
    }
}
