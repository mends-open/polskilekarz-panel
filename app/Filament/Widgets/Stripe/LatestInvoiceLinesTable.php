<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Dashboard\Concerns\RefreshesDashboardContextOnBoot;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Stripe\StripeObject;

class LatestInvoiceLinesTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use HasStripeInvoiceForm;
    use InteractsWithDashboardContext;
    use RefreshesDashboardContextOnBoot;
    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('filament.widgets.stripe.latest_invoice_lines_table.heading');
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady(
            fn (): bool => $this->chatwootContext()->hasContact(),
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.widgets.stripe.latest_invoice_lines_table.heading'))
            ->paginated(false)
            ->records(fn () => $this->resolveLineItems())
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentList)
            ->emptyStateHeading(__('filament.widgets.stripe.latest_invoice_lines_table.empty_state.heading'))
            ->emptyStateDescription(__('filament.widgets.stripe.latest_invoice_lines_table.empty_state.description'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('pricing.price_details.price')
                            ->label(__('filament.widgets.stripe.latest_invoice_lines_table.columns.price.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray'),
                        TextColumn::make('pricing.price_details.product')
                            ->label(__('filament.widgets.stripe.latest_invoice_lines_table.columns.product.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray'),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('description')
                            ->label(__('filament.widgets.stripe.latest_invoice_lines_table.columns.description.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->wrap(),
                    ]),
                    Stack::make([
                        TextColumn::make('pricing.unit_amount_decimal')
                            ->label(__('filament.widgets.stripe.latest_invoice_lines_table.columns.unit_amount.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->money(
                                currency: fn ($record) => data_get($record, 'currency'),
                                divideBy: fn ($record) => $this->currencyDivisor((string) data_get($record, 'currency', '')),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces((string) data_get($record, 'currency', '')),
                            ),
                        TextColumn::make('quantity')
                            ->label(__('filament.widgets.stripe.latest_invoice_lines_table.columns.quantity.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray')
                            ->prefix(__('filament.widgets.stripe.latest_invoice_lines_table.columns.quantity.prefix')),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label(__('filament.widgets.stripe.latest_invoice_lines_table.columns.amount.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('primary')
                            ->money(
                                currency: fn ($record) => data_get($record, 'currency'),
                                divideBy: fn ($record) => $this->currencyDivisor((string) data_get($record, 'currency', '')),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces((string) data_get($record, 'currency', '')),
                            ),
                    ]),
                ]),
            ])
            ->headerActions([
                $this->configureInvoiceFormAction(
                    Action::make('duplicateLatestInvoice')
                        ->label(__('filament.widgets.stripe.latest_invoice_lines_table.actions.duplicate.label'))
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->outlined()
                        ->color($this->latestInvoice ? 'primary' : 'gray')
                        ->disabled(! $this->latestInvoice)
                        ->modalHeading(__('filament.widgets.stripe.latest_invoice_lines_table.actions.duplicate.modal.heading'))
                )->fillForm(function (): array {
                    $invoice = $this->latestInvoice;

                    if (! $invoice instanceof StripeObject) {
                        return [];
                    }

                    return $this->getInvoiceFormDefaults($invoice->toArray());
                }),
            ]);
    }

    protected function resolveLineItems(): array
    {
        return collect($this->latestInvoiceLines ?? [])
            ->map(fn ($line) => $this->formatLineItem($line))
            ->all();
    }

    private function refreshLines(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->clearLatestInvoiceCache();
        $this->resetInvoiceFormCache();
    }

    #[On('stripe.invoices.refresh')]
    public function handleInvoiceRefresh(): void
    {
        $this->refreshLines();
    }

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->refreshLines();
    }

    private function formatLineItem(StripeObject|array|null $line): array
    {
        $payload = $line instanceof StripeObject
            ? $line->toArray()
            : (array) ($line ?? []);

        $description = $payload['description']
            ?? data_get($payload, 'price.product.name')
            ?? data_get($payload, 'price.nickname')
            ?? data_get($payload, 'price.id');

        if (is_string($description)) {
            $payload['description'] = $description;
        }

        $amount = $payload['amount']
            ?? data_get($payload, 'amount_excluding_tax')
            ?? data_get($payload, 'price.unit_amount');

        if (is_numeric($amount)) {
            $payload['amount'] = $amount;
        }

        $currency = $payload['currency'] ?? data_get($payload, 'price.currency');

        if (! is_string($currency) || $currency === '') {
            $currency = (string) config('cashier.currency', 'usd');
        }

        $payload['currency'] = Str::lower($currency);

        return $payload;
    }
}
