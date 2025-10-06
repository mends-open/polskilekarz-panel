<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\On;

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
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('unit_amount')
                    ->label('Unit Price')
                    ->badge()
                    ->money(
                        currency: fn ($record) => $record['currency'],
                        divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                        locale: config('app.locale'),
                        decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                    ),
                TextColumn::make('amount')
                    ->label('Subtotal')
                    ->badge()
                    ->color('primary')
                    ->money(
                        currency: fn ($record) => $record['currency'],
                        divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                        locale: config('app.locale'),
                        decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                    ),
            ])
            ->headerActions([
                $this->configureInvoiceFormAction(
                    Action::make('duplicateLatest')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->outlined()
                        ->color($this->latestInvoice ? 'primary' : 'gray')
                        ->disabled(! $this->latestInvoice)
                        ->modalIcon(Heroicon::OutlinedDocumentDuplicate)
                        ->modalHeading('Duplicate latest invoice')
                )->fillForm(function (): array {
                    $invoice = $this->latestInvoice;

                    if ($invoice === null) {
                        return $this->getInvoiceFormDefaults(null);
                    }

                    return $this->getInvoiceFormDefaults($this->stripePayload($invoice));
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
        $invoice = $this->latestInvoice;

        if ($invoice === null) {
            return [];
        }

        $invoicePayload = $this->stripePayload($invoice);
        $invoiceCurrency = (string) data_get($invoicePayload, 'currency');

        return collect($this->latestInvoiceLines)
            ->map(fn ($line) => $this->formatLineItem($line, $invoiceCurrency))
            ->filter(fn (array $line) => filled($line['description']))
            ->values()
            ->all();
    }

    private function refreshLines(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->clearLatestInvoiceCache();
        unset($this->stripePriceCollection, $this->stripeProductCollection);
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

    protected function afterInvoiceFormHandled(): void
    {
        $this->refreshLines();
    }

    private function formatLineItem(mixed $line, string $invoiceCurrency): array
    {
        $payload = $this->stripePayload($line);

        $description = data_get($payload, 'description')
            ?? data_get($payload, 'price.product.name')
            ?? data_get($payload, 'price.nickname')
            ?? data_get($payload, 'price.id');

        $quantity = max(1, (int) data_get($payload, 'quantity', 1));
        $amount = (int) data_get($payload, 'amount', 0);
        $unitAmount = (int) data_get($payload, 'price.unit_amount', $quantity > 0 ? (int) round($amount / $quantity) : 0);

        return [
            'id' => data_get($payload, 'id'),
            'description' => $description,
            'quantity' => $quantity,
            'currency' => (string) data_get($payload, 'currency', $invoiceCurrency),
            'amount' => $amount,
            'unit_amount' => $unitAmount,
        ];
    }
}
