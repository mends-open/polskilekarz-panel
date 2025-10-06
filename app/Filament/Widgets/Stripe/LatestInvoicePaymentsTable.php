<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class LatestInvoicePaymentsTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 5;

    protected static ?string $heading = 'Latest Invoice Payments';

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    #[Computed(persist: true)]
    protected function latestInvoicePayments(): array
    {
        $invoice = $this->latestInvoice;

        if ($invoice === []) {
            return [];
        }

        $invoiceCurrency = (string) data_get($invoice, 'currency');

        return collect(data_get($invoice, 'payments.data', []))
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

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $payments = collect($this->latestInvoicePayments);

                $records = $payments
                    ->forPage($page, $recordsPerPage)
                    ->values()
                    ->all();

                return new LengthAwarePaginator(
                    items: $records,
                    total: $payments->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25, 50])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')
                            ->label('Payment')
                            ->badge()
                            ->color('gray'),
                    ])->grow(),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label('Amount')
                            ->money(
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                            )
                            ->badge()
                            ->color(fn ($record) => match ($record['status']) {
                                'succeeded' => 'success',
                                'processing' => 'warning',
                                'requires_payment_method', 'requires_action', 'requires_confirmation', 'requires_capture' => 'danger',
                                'canceled' => 'gray',
                                default => 'secondary',
                            }),
                    ])->grow(false),
                    Stack::make([
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
                    ])->grow(false),
                    Stack::make([
                        TextColumn::make('method')
                            ->label('Method')
                            ->badge()
                            ->color('warning'),
                    ])->grow(false),
                    Stack::make([
                        TextColumn::make('created')
                            ->label('Created')
                            ->since(),
                    ])->grow(false),
                ]),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->action(fn () => $this->refreshTable())
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

    private function refreshTable(): void
    {
        $this->resetComponent();
        $this->clearLatestInvoiceCache();
        unset($this->latestInvoicePayments);
    }

    private function resetComponent(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    #[On('stripe.invoices.refresh')]
    public function handleInvoiceRefresh(): void
    {
        $this->refreshTable();
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshTable();
    }
}
