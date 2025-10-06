<?php

namespace App\Support\Currency;

trait ResolvesCurrencyPrecision
{
    protected function resolveCurrencyMinorUnitExponent(?string $currency): int
    {
        if (blank($currency)) {
            return 2;
        }

        $currency = strtolower($currency);

        if (in_array($currency, $this->zeroDecimalCurrencies(), true)) {
            return 0;
        }

        return 2;
    }

    protected function resolveCurrencyMinorUnitDivisor(?string $currency): int
    {
        return (int) (10 ** $this->resolveCurrencyMinorUnitExponent($currency));
    }

    protected function resolveCurrencyDecimalPlaces(?string $currency): int
    {
        return $this->resolveCurrencyMinorUnitExponent($currency);
    }

    /**
     * @return array<int, string>
     */
    protected function zeroDecimalCurrencies(): array
    {
        return [
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
    }
}
