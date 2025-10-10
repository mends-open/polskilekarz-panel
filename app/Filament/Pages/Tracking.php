<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Cloudflare\LinkEntriesTable;
use App\Filament\Widgets\Cloudflare\LinksTable;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Tracking extends Dashboard
{
    protected static string $routePath = 'tracking';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedMagnifyingGlass;
    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::OutlinedMagnifyingGlass;

    public function getTitle(): string|Htmlable
    {
        return __('filament.pages.tracking.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.pages.tracking.navigation.label');
    }
    public function getWidgets(): array
    {
        return [
            LinksTable::class,
            LinkEntriesTable::class,
        ];
    }
}
