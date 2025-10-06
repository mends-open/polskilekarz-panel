<?php

namespace App\Support\Stripe;

use Illuminate\Support\Str;

class Currency
{
    /**
     * List of zero-decimal currencies as defined by Stripe.
     *
     * @link https://stripe.com/docs/currencies#zero-decimal
     */
    protected const ZERO_DECIMAL_CURRENCIES = [
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

    /**
     * Stripe currencies that operate with three decimal places.
     *
     * @link https://stripe.com/docs/currencies#special-cases
     */
    protected const THREE_DECIMAL_CURRENCIES = [
        'bhd',
        'jod',
        'kwd',
        'omr',
        'tnd',
    ];

    public static function decimalPlaces(?string $currency): int
    {
        $currency = Str::lower((string) $currency);

        if ($currency === '') {
            return 2;
        }

        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true)) {
            return 0;
        }

        if (in_array($currency, self::THREE_DECIMAL_CURRENCIES, true)) {
            return 3;
        }

        return 2;
    }

    public static function isZeroDecimal(?string $currency): bool
    {
        return self::decimalPlaces($currency) === 0;
    }

    public static function divisor(?string $currency): int
    {
        return 10 ** self::decimalPlaces($currency);
    }

    public static function toFloat(int|float $amount, ?string $currency): float
    {
        return $amount / self::divisor($currency);
    }

    public static function fromFloat(float $amount, ?string $currency): int
    {
        return (int) round($amount * self::divisor($currency));
    }

    public static function format(int $amount, ?string $currency, string $decimalSeparator = '.', string $thousandsSeparator = ' '): string
    {
        $currency = Str::upper((string) $currency);

        if ($currency === '') {
            return '—';
        }

        $decimalPlaces = self::decimalPlaces($currency);
        $value = self::toFloat($amount, $currency);

        return sprintf(
            '%s %s',
            number_format($value, $decimalPlaces, $decimalSeparator, $thousandsSeparator),
            $currency,
        );
    }
}
