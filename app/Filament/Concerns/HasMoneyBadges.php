<?php

namespace App\Filament\Concerns;

use App\Support\Stripe\Currency as StripeCurrency;
use BackedEnum;
use Closure;
use Illuminate\Support\Arr;

trait HasMoneyBadges
{
    protected function moneyCurrency(string|array|null $path = 'currency', BackedEnum|Closure|string|null $fallback = null): Closure
    {
        $paths = $this->prepareMoneyCurrencyPaths($path);

        return function ($record = null, $state = null) use ($paths, $fallback): ?string {
            return $this->resolveMoneyCurrency($record, $state, $paths, $fallback);
        };
    }

    protected function moneyDivideBy(
        int $defaultDivideBy = 100,
        string|array|null $currencyPath = 'currency',
        BackedEnum|Closure|string|null $fallback = null,
    ): Closure {
        $paths = $this->prepareMoneyCurrencyPaths($currencyPath);

        return function ($record = null, $state = null) use ($defaultDivideBy, $paths, $fallback): int {
            $currency = $this->resolveMoneyCurrency($record, $state, $paths, $fallback);

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

    protected function resolveMoneyCurrency(
        $record,
        $state,
        array $paths,
        BackedEnum|Closure|string|null $fallback,
    ): ?string {
        if ($paths !== []) {
            foreach ([$state, $record] as $source) {
                if ($source === null) {
                    continue;
                }

                foreach ($paths as $path) {
                    $normalized = $this->normalizeCurrencyValue(data_get($source, $path));

                    if ($normalized !== null) {
                        return $normalized;
                    }
                }
            }
        }

        if ($fallback === null) {
            return null;
        }

        $value = $fallback instanceof Closure
            ? $fallback($record, $state)
            : $fallback;

        return $this->normalizeCurrencyValue($value);
    }

    protected function prepareMoneyCurrencyPaths(string|array|null $paths): array
    {
        if ($paths === null) {
            return [];
        }

        return array_values(array_filter(Arr::wrap($paths), static fn ($path) => $path !== null && $path !== ''));
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
