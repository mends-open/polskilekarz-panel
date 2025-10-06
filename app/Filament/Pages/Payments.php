<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Chatwoot\ContactInfolist;
use App\Filament\Widgets\Stripe\CustomerInfolist;
use App\Filament\Widgets\Stripe\LatestInvoiceInfolist;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InteractsWithStripeInvoices;
use App\Filament\Widgets\Stripe\LatestPaymentInfolist;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Filament\Widgets\Stripe\InvoicesTable;
use App\Filament\Widgets\Stripe\PaymentsTable;
use Filament\Pages\Dashboard;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class Payments extends Dashboard
{
    use InteractsWithDashboardContext;
    use HasStripeInvoiceForm;
    use InteractsWithStripeInvoices;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $title = 'Payments';

    protected function getHeaderActions(): array
    {
        return [
            $this->configureInvoiceFormAction(
                Action::make('createInvoice')
                    ->label('Create new')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('success')
                    ->outlined()
                    ->modalIcon(Heroicon::OutlinedDocumentPlus)
                    ->modalHeading('Create invoice')
            ),
        ];
    }

    public function getWidgets(): array
    {
        return [
            ContactInfolist::class,
            CustomerInfolist::class,
            LatestInvoiceInfolist::class,
            LatestPaymentInfolist::class,
            InvoicesTable::class,
            PaymentsTable::class,
        ];
    }

}
