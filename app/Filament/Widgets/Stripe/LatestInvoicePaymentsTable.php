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
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class LatestInvoicePaymentsTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest Invoice Payments';

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->records(fn () => $this->resolvePayments())
            ->columns([
                TextColumn::make('id')
                    ->label('Payment')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->badge()
                    ->money(
                        currency: fn ($record) => $record['currency'],
                        divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                        locale: config('app.locale'),
                        decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                    )
                    ->color(fn ($record) => match ($record['status']) {
                        'succeeded' => 'success',
                        'processing' => 'warning',
                        'requires_payment_method', 'requires_action', 'requires_confirmation', 'requires_capture' => 'danger',
                        'canceled' => 'gray',
                        default => 'secondary',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'succeeded' => 'success',
                        'processing' => 'warning',
                        'requires_payment_method', 'requires_action', 'requires_confirmation', 'requires_capture' => 'danger',
                        'canceled' => 'gray',
                        default => 'secondary',
                    }),
                TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('created')
                    ->label('Created')
                    ->since(),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->action(fn () => $this->refreshPayments())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
            ])
            ->recordActions([
                Action::make('openReceipt')
                    ->label('Open Receipt')
                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->url(fn ($record) => $record['receipt_url'] ?? null)
                    ->openUrlInNewTab()
                    ->hidden(fn ($record) => blank($record['receipt_url'] ?? null)),
            ]);
    }

    protected function resolvePayments(): array
    {
        $invoice = $this->latestInvoice;

        if ($invoice === []) {
            return [];
        }

        $invoiceCurrency = (string) data_get($invoice, 'currency');

        return collect($this->latestInvoicePayments)
            ->map(function ($payment) use ($invoiceCurrency) {
                $payment = is_array($payment) ? $payment : (array) $payment;
                $details = data_get($payment, 'payment');
                $details = is_array($details) ? $details : (array) $details;

                $currency = (string) data_get($details, 'currency', data_get($payment, 'currency', $invoiceCurrency));
                $amount = (int) data_get($details, 'amount', data_get($payment, 'amount', 0));
                $status = (string) data_get($details, 'status', data_get($payment, 'status', ''));
                $created = (int) data_get($details, 'created', data_get($payment, 'created'));
                $method = data_get($details, 'payment_method_details.type')
                    ?? data_get($details, 'payment_method.type')
                    ?? data_get($payment, 'payment_method_details.type');
                $method = is_string($method) ? Str::upper($method) : null;

                return [
                    'id' => data_get($details, 'id', data_get($payment, 'id')),
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $status,
                    'created' => $created,
                    'method' => $method,
                    'receipt_url' => data_get($details, 'charges.data.0.receipt_url'),
                ];
            })
            ->values()
            ->all();
    }

    private function refreshPayments(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->clearLatestInvoiceCache();
    }

    #[On('stripe.invoices.refresh')]
    public function handleInvoiceRefresh(): void
    {
        $this->refreshPayments();
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshPayments();
    }
}
