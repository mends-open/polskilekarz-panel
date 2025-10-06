<?php

namespace App\Filament\Concerns;

use App\Support\Stripe\Currency as StripeCurrency;
use BackedEnum;
use Closure;

trait HasMoneyBadges
{
    protected function moneyCurrency(string|array|null $path = 'currency', BackedEnum|Closure|string|null $fallback = null): Closure
    {
        return function ($record = null, $state = null) use ($path, $fallback): ?string {
            return $this->resolveCurrencyForMoneyBadge($record, $state, $path, $fallback);
        };
    }

    protected function moneyDivideBy(
        int $defaultDivideBy = 100,
        string|array|null $currencyPath = 'currency',
        BackedEnum|Closure|string|null $fallback = null,
    ): Closure {
        return function ($record = null, $state = null) use ($defaultDivideBy, $currencyPath, $fallback): int {
            $currency = $this->resolveCurrencyForMoneyBadge($record, $state, $currencyPath, $fallback);

            if ($currency !== null && StripeCurrency::isZeroDecimal($currency)) {
                return 1;
            }

            return $defaultDivideBy;
        };
    }

    protected function moneyLocale(?string $locale = null): ?string
    {
        return $locale;
    }

    protected function moneyDecimalPlaces(?int $decimalPlaces = null): ?int
    {
        return $decimalPlaces;
    }

    protected function resolveCurrencyForMoneyBadge(
        $record,
        $state,
        string|array|null $paths,
        BackedEnum|Closure|string|null $fallback,
    ): ?string {
        $paths = $paths === null ? [] : (array) $paths;

        $targets = array_values(array_filter([
            $state,
            $record !== $state ? $record : null,
        ], static fn ($value) => $value !== null));

        if ($paths !== []) {
            foreach ($targets as $target) {
                foreach ($paths as $path) {
                    $currency = data_get($target, $path);

                    $normalized = $this->normalizeCurrencyValue($currency);

                    if ($normalized !== null) {
                        return $normalized;
                    }
                }
            }
        }

        if ($fallback === null) {
            return null;
        }

        $currency = $fallback instanceof Closure
            ? $fallback($record, $state, $targets[0] ?? null)
            : $fallback;

        return $this->normalizeCurrencyValue($currency);
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
