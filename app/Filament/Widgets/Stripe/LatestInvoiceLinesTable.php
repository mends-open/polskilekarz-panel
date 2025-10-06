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
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
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
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                        ),
                    ])
                ]),
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
        return $this->latestInvoiceLines ?? [];

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
