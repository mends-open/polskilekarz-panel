<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Chatwoot\ContactInfolist;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InteractsWithStripeInvoices;
use App\Filament\Widgets\Stripe\CustomerInfolist;
use App\Filament\Widgets\Stripe\InvoicesTable;
use App\Filament\Widgets\Stripe\LatestInvoiceInfolist;
use App\Filament\Widgets\Stripe\LatestInvoiceLinesTable;
use App\Filament\Widgets\Stripe\PaymentsTable;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Payments extends Dashboard
{
    use HasStripeInvoiceForm;
    use InteractsWithDashboardContext;
    use InteractsWithStripeInvoices;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament.pages.payments.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.pages.payments.navigation.label');
    }

    public function getWidgets(): array
    {
        return [
            ContactInfolist::class,
            CustomerInfolist::class,
            LatestInvoiceInfolist::class,
            LatestInvoiceLinesTable::class,
            InvoicesTable::class,
            PaymentsTable::class,
        ];
    }
}
