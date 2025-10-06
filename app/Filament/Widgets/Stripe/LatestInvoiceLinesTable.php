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
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class LatestInvoiceLinesTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 5;

    protected static ?string $heading = 'Latest Invoice Items';

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    #[Computed(persist: true)]
    protected function latestInvoiceLineItems(): array
    {
        $invoice = $this->latestInvoice;

        if ($invoice === []) {
            return [];
        }

        $invoiceCurrency = (string) data_get($invoice, 'currency');

        return collect(data_get($invoice, 'lines.data', []))
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

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $items = collect($this->latestInvoiceLineItems);

                $records = $items
                    ->forPage($page, $recordsPerPage)
                    ->values()
                    ->all();

                return new LengthAwarePaginator(
                    items: $records,
                    total: $items->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25, 50])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('description')
                            ->label('Description')
                            ->wrap(),
                    ])->grow(),
                    Stack::make([
                        TextColumn::make('quantity')
                            ->label('Quantity')
                            ->badge()
                            ->color('gray'),
                    ])->grow(false),
                    Stack::make([
                        TextColumn::make('unit_amount')
                            ->label('Unit Price')
                            ->money(
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                            )
                            ->badge(),
                    ])->grow(false),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label('Subtotal')
                            ->money(
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                            )
                            ->badge()
                            ->color('primary'),
                    ])->grow(false),
                ]),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->action(fn () => $this->refreshTable())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
            ]);
    }

    private function refreshTable(): void
    {
        $this->resetComponent();
        $this->clearLatestInvoiceCache();
        unset($this->latestInvoiceLineItems);
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
