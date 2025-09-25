<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Payments extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;
}
