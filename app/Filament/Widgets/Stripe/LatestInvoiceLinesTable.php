<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
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

        if ($invoice === []) {
            return [];
        }

        $invoiceCurrency = (string) data_get($invoice, 'currency');

        return collect($this->latestInvoiceLines)
            ->map(function ($line) use ($invoiceCurrency) {
                $line = is_array($line) ? $line : (array) $line;

                $currency = (string) data_get($line, 'currency', $invoiceCurrency);
                $quantity = max(1, (int) data_get($line, 'quantity', 1));
                $amount = (int) data_get($line, 'amount', 0);
                $unitAmount = (int) data_get($line, 'price.unit_amount', $quantity > 0 ? (int) round($amount / $quantity) : 0);

                return [
                    'id' => data_get($line, 'id'),
                    'description' => data_get($line, 'description'),
                    'quantity' => $quantity,
                    'currency' => $currency,
                    'amount' => $amount,
                    'unit_amount' => $unitAmount,
                ];
            })
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
}
