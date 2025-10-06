<?php

namespace App\Filament\Concerns;

use BackedEnum;
use Closure;

trait HasMoneyBadges
{
    protected function moneyCurrency(?string $path = 'currency', BackedEnum|Closure|string|null $fallback = null): Closure
    {
        return function ($record = null, $state = null) use ($path, $fallback): ?string {
            $target = $record ?? $state;

            $currency = $path === null ? null : data_get($target, $path);
            $currency = $this->normalizeCurrencyValue($currency);

            if ($currency === null && $fallback !== null) {
                $currency = $fallback instanceof Closure
                    ? $this->normalizeCurrencyValue($fallback($record, $state, $target))
                    : $this->normalizeCurrencyValue($fallback);
            }

            return $currency;
        };
    }

    protected function moneyDivideBy(int $divideBy = 100): int
    {
        return $divideBy;
    }

    protected function moneyLocale(?string $locale = null): ?string
    {
        return $locale;
    }

    protected function moneyDecimalPlaces(?int $decimalPlaces = null): ?int
    {
        return $decimalPlaces;
    }

    protected function normalizeCurrencyValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return strtoupper($value);
    }
}
