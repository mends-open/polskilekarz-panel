<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Chatwoot\ContactInfolist;
use App\Filament\Widgets\Stripe\CustomerInfolist;
use App\Filament\Widgets\Stripe\InvoicesTable;
use App\Filament\Widgets\Stripe\PaymentsTable;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

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
            CustomerInfolist::class,
            ContactInfolist::class,
            InvoicesTable::class,
            PaymentsTable::class,
        ];
    }
}
