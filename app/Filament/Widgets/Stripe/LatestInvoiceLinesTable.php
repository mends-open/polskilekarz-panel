<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Illuminate\Support\Str;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Stripe\StripeObject;

class LatestInvoiceLinesTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use HasStripeInvoiceForm;
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest Invoice Items';

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->records(fn () => $this->resolveLineItems())
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('pricing.price_details.price')
                            ->badge()
                            ->color('gray'),
                        TextColumn::make('pricing.price_details.product')
                            ->badge()
                            ->color('gray'),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('description')
                            ->label('Description')
                            ->wrap(),
                    ]),
                    Stack::make([
                        TextColumn::make('pricing.unit_amount_decimal')
                            ->label('Unit Price')
                            ->badge()
                            ->money(
                                currency: fn ($record) => data_get($record, 'currency'),
                                divideBy: fn ($record) => $this->currencyDivisor((string) data_get($record, 'currency', '')),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces((string) data_get($record, 'currency', '')),
                            ),
                        TextColumn::make('quantity')
                            ->label('Qty')
                            ->badge()
                            ->color('gray')
                            ->prefix('x'),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label('Subtotal')
                            ->badge()
                            ->color('primary')
                            ->money(
                                currency: fn ($record) => data_get($record, 'currency'),
                                divideBy: fn ($record) => $this->currencyDivisor((string) data_get($record, 'currency', '')),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces((string) data_get($record, 'currency', '')),
                        ),
                    ])
                ]),
            ])
            ->headerActions([
                $this->configureInvoiceFormAction(
                    Action::make('duplicateLatestInvoice')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->outlined()
                        ->color($this->latestInvoice ? 'primary' : 'gray')
                        ->disabled(! $this->latestInvoice)
                        ->modalHeading('Duplicate latest invoice')
                )->fillForm(function (): array {
                    $invoice = $this->latestInvoice;

                    if (! $invoice instanceof StripeObject) {
                        return [];
                    }

                    return $this->getInvoiceFormDefaults($invoice->toArray());
                }),
                Action::make('refresh')
                    ->action(fn () => $this->refreshLines())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
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
    }

    #[On('stripe.invoices.refresh')]
    public function handleInvoiceRefresh(): void
    {
        $this->refreshLines();
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
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
