<?php

namespace App\Filament\Widgets\Concerns;

use BackedEnum;
use Closure;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

trait HasMoneyBadges
{
    /**
     * @template T of TextColumn|TextEntry
     *
     * @param  T  $component
     * @return T
     */
    protected function moneyBadge(
        TextColumn|TextEntry $component,
        BackedEnum|string|Closure|null $currency = null,
        int|Closure $divideBy = 100,
        BackedEnum|string|Closure|null $locale = null,
        int|Closure|null $decimalPlaces = null,
    ): TextColumn|TextEntry {
        $component->badge();

        return $component->money($currency, $divideBy, $locale, $decimalPlaces);
    }
}
