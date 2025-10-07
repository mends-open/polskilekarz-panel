<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InterpretsStripeAmounts;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Stripe\StripeObject;

class LatestInvoiceInfolist extends BaseSchemaWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use HasStripeInvoiceForm;
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;

    protected int|string|array $columnSpan = 'full';

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    protected function afterInvoiceFormHandled(): void
    {
        $this->refreshLatestInvoice();
    }

    #[On('stripe.invoices.refresh')]
    public function refreshLatestInvoice(): void
    {
        $this->clearLatestInvoiceCache();
        unset($this->stripePriceCollection, $this->stripeProductCollection);
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshLatestInvoice();
    }

    public function schema(Schema $schema): Schema
    {
        $invoice = $this->latestInvoice;
        $data = $invoice instanceof StripeObject ? $invoice->toArray() : [];
        $currency = (string) data_get($data, 'currency');
        $divideBy = $currency === '' ? 100 : $this->currencyDivisor($currency);
        $decimalPlaces = $currency === '' ? 2 : $this->currencyDecimalPlaces($currency);

        return $schema
            ->state($data)
            ->components([
                Section::make(__('filament.widgets.stripe.latest_invoice_infolist.section.title'))
                    ->columns(2)
                    ->headerActions([
                        $this->configureInvoiceFormAction(
                            Action::make('createInvoice')
                                ->label(__('filament.widgets.stripe.latest_invoice_infolist.actions.create_invoice.label'))
                                ->icon(Heroicon::OutlinedDocumentPlus)
                                ->outlined()
                                ->color('success')
                                ->modalHeading(__('filament.widgets.stripe.latest_invoice_infolist.actions.create_invoice.modal.heading'))
                        ),
                        Action::make('sendLatest')
                            ->label(__('filament.widgets.stripe.latest_invoice_infolist.actions.send_latest.label'))
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->color(blank($data) ? 'gray' : 'warning')
                            ->disabled(blank($data))
                            ->action(fn () => $this->sendHostedInvoiceLink($invoice)),
                        Action::make('openInvoice')
                            ->label(__('filament.widgets.stripe.latest_invoice_infolist.actions.open_latest.label'))
                            ->outlined()
                            ->color(blank($data) ? 'gray' : 'primary')
                            ->disabled(blank($data))
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url(data_get($data, 'hosted_invoice_url'))
                            ->openUrlInNewTab()
                            ->hidden(blank(data_get($data, 'hosted_invoice_url'))),
                        Action::make('reset')
                            ->action(fn () => $this->refreshLatestInvoice())
                            ->hiddenLabel()
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->link(),
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'draft', 'void' => 'gray',
                                'open' => 'warning',
                                'paid' => 'success',
                                'uncollectible' => 'danger',
                                default => 'secondary',
                            })
                            ->inlineLabel(),
                        TextEntry::make('created')
                            ->since()
                            ->inlineLabel(),
                        TextEntry::make('due_date')
                            ->since()
                            ->inlineLabel(),
                        TextEntry::make('total')
                            ->label(__('filament.widgets.stripe.latest_invoice_infolist.fields.total.label'))
                            ->inlineLabel()
                            ->badge()
                            ->money(
                                currency: $currency,
                                divideBy: $divideBy,
                                locale: config('app.locale'),
                                decimalPlaces: $decimalPlaces,
                            ),
                        TextEntry::make('amount_paid')
                            ->label(__('filament.widgets.stripe.latest_invoice_infolist.fields.amount_paid.label'))
                            ->inlineLabel()
                            ->color('success')
                            ->money(
                                currency: $currency,
                                divideBy: $divideBy,
                                locale: config('app.locale'),
                                decimalPlaces: $decimalPlaces,
                            )
                            ->badge(),
                        TextEntry::make('amount_remaining')
                            ->label(__('filament.widgets.stripe.latest_invoice_infolist.fields.amount_remaining.label'))
                            ->color('danger')
                            ->inlineLabel()
                            ->money(
                                currency: $currency,
                                divideBy: $divideBy,
                                locale: config('app.locale'),
                                decimalPlaces: $decimalPlaces,
                            )
                            ->badge(),
                        TextEntry::make('collection_method')
                            ->inlineLabel()
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'charge_automatically' => 'success',
                                'send_invoice' => 'warning',
                                default => 'secondary',
                            }),
                    ]),
            ]);
    }
}
