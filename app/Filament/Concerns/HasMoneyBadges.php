<?php

namespace App\Filament\Concerns;

use App\Support\Stripe\Currency as StripeCurrency;
use BackedEnum;
use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Arr;
use ReflectionFunction;
use ReflectionNamedType;

trait HasMoneyBadges
{
    protected function moneyCurrency(string|array|null $path = 'currency', BackedEnum|Closure|string|null $fallback = null): Closure
    {
        $paths = $this->prepareMoneyCurrencyPaths($path);

        return function (?Get $get = null, $record = null, $state = null) use ($paths, $fallback): ?string {
            return $this->resolveMoneyCurrency($get, $record, $state, $paths, $fallback);
        };
    }

    protected function moneyDivideBy(
        int $defaultDivideBy = 100,
        string|array|null $currencyPath = 'currency',
        BackedEnum|Closure|string|null $fallback = null,
    ): Closure {
        $paths = $this->prepareMoneyCurrencyPaths($currencyPath);

        return function (?Get $get = null, $record = null, $state = null) use ($defaultDivideBy, $paths, $fallback): int {
            $currency = $this->resolveMoneyCurrency($get, $record, $state, $paths, $fallback);

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
        ?Get $get,
        $record,
        $state,
        array $paths,
        BackedEnum|Closure|string|null $fallback,
    ): ?string {
        if ($paths !== []) {
            if ($get !== null) {
                foreach ($paths as $path) {
                    $normalized = $this->normalizeCurrencyValue($this->getCurrencyValueFromGet($get, $path));

                    if ($normalized !== null) {
                        return $normalized;
                    }
                }
            }

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
            ? $this->evaluateMoneyFallback($fallback, $get, $record, $state)
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

    protected function getCurrencyValueFromGet(Get $get, string $path): mixed
    {
        if ($path === '') {
            return $get();
        }

        return $get($path);
    }

    protected function evaluateMoneyFallback(Closure $fallback, ?Get $get, $record, $state): mixed
    {
        $reflection = new ReflectionFunction($fallback);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof ReflectionNamedType && (! $type->isBuiltin())) {
                $typeName = $type->getName();

                if ($get !== null && is_a($typeName, Get::class, true)) {
                    $arguments[] = $get;

                    continue;
                }
            }

            if ($name === 'get' && $get !== null) {
                $arguments[] = $get;

                continue;
            }

            if ($name === 'record') {
                $arguments[] = $record;

                continue;
            }

            if ($name === 'state') {
                $arguments[] = $state;

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;

                continue;
            }

            $arguments[] = null;
        }

        return $fallback(...$arguments);
    }
}
