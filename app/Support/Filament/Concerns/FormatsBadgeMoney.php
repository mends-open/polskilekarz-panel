<?php

namespace App\Support\Filament\Concerns;

use App\Support\Currency\ResolvesCurrencyPrecision;
use Closure;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

trait FormatsBadgeMoney
{
    use ResolvesCurrencyPrecision;

    protected function formatBadgeMoney(
        TextEntry|TextColumn $component,
        Closure|string|null $currency = null,
        Closure|int|null $divideBy = null,
        Closure|string|null $locale = null,
        Closure|int|null $decimalPlaces = null,
    ): TextEntry|TextColumn {
        $resolveCurrency = function (TextEntry|TextColumn $component) use ($currency): ?string {
            if ($currency === null) {
                return null;
            }

            $resolvedCurrency = $component->evaluate($currency);

            return is_string($resolvedCurrency) ? strtolower($resolvedCurrency) : $resolvedCurrency;
        };

        $divideBy ??= function (TextEntry|TextColumn $component) use ($resolveCurrency): int {
            return $this->resolveCurrencyMinorUnitDivisor($resolveCurrency($component));
        };

        $decimalPlaces ??= function (TextEntry|TextColumn $component) use ($resolveCurrency): int {
            return $this->resolveCurrencyDecimalPlaces($resolveCurrency($component));
        };

        return $component
            ->badge()
            ->money(
                $currency,
                $divideBy,
                $locale,
                $decimalPlaces,
            );
    }
}
