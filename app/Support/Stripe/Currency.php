<?php

namespace App\Support\Stripe;

class Currency
{
    /**
     * List of zero-decimal currencies as defined by Stripe.
     *
     * @link https://stripe.com/docs/currencies#zero-decimal
     */
    protected const ZERO_DECIMAL_CURRENCIES = [
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'UGX',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    ];

    public static function isZeroDecimal(string $currency): bool
    {
        return in_array(self::normalize($currency), self::ZERO_DECIMAL_CURRENCIES, true);
    }

    protected static function normalize(string $currency): string
    {
        return strtoupper(trim($currency));
    }
}
