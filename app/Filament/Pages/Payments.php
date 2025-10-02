<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Chatwoot\ContactInfolist;
use App\Filament\Widgets\Stripe\CustomerInfolist;
use App\Filament\Widgets\Stripe\InvoicesTable;
use App\Filament\Widgets\Stripe\PaymentsTable;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class Payments extends Dashboard
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $title = 'Payments';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [
            ContactInfolist::class,
            CustomerInfolist::class,
            InvoicesTable::class,
            PaymentsTable::class,
        ];
    }
}
