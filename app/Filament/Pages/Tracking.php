<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Cloudflare\LinkEntriesTable;
use App\Filament\Widgets\Cloudflare\LinksTable;
use App\Filament\Widgets\Cloudflare\LinkTable;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\VarDumper\Caster\LinkStub;

class Tracking extends Dashboard
{
    protected static string $routePath = 'tracking';

    protected static ?string $title = 'Tracking';

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::OutlinedMagnifyingGlass;
    public function getWidgets(): array
    {
        return [
            LinksTable::class,
            LinkEntriesTable::class,
        ];
    }
}
