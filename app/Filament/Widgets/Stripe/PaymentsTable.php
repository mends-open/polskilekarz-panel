<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;

class PaymentsTable extends BaseTableWidget
{
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Payments';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[On('reset')]
    public function resetComponent(): void
    {
        $this->forgetComputed('customerPayments');
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function refreshTable(): void
    {
        $this->forgetComputed('customerPayments');
        $this->resetComponent();
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->customerPayments)
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')
                            ->color('gray')
                            ->badge(),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('amount')
                            ->state(fn ($record) => $record['amount'] / 100)
                            ->badge()
                            ->money(fn ($record) => $record['currency'])
                            ->color(fn ($record) => match ($record['status']) {
                                'succeeded' => 'success',   // ✅ received
                                default => 'gray',          // ❌ not yet settled
                            }),
                        TextColumn::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'succeeded' => 'success',             // green
                                'processing' => 'warning',            // yellow
                                'requires_payment_method', 'requires_capture', 'requires_action', 'requires_confirmation' => 'danger',// red
                                'canceled' => 'gray',                 // neutral
                                default => 'secondary',
                            }),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('created')
                            ->since(),
                    ])->space(2),
                ]),
            ])
            ->filters([])
            ->headerActions([
                Action::make('refresh')
                    ->action(fn () => $this->refreshTable())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('openPaymentUrl')
                        ->url(fn ($record) => $record['charges']['data'][0]['receipt_url'] ?? null)
                        ->openUrlInNewTab()
                        ->label('Open Receipt')
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->hidden(fn ($record) => blank(data_get($record, 'charges.data.0.receipt_url'))),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ApiErrorException
     * @throws NotFoundExceptionInterface
     */
    #[Computed(cache: true)]
    public function customerPayments(): array
    {
        $customerId = (string) $this->stripeContext()->customerId;

        return $customerId ? stripe()->paymentIntents->all([
            'customer' => $customerId,
        ])->toArray()['data'] : [];
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->forgetComputed('customerPayments');
        $this->resetTable();
    }

}
