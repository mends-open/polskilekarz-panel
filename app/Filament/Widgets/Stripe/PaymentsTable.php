<?php

namespace App\Filament\Widgets\Stripe;

use Filament\Actions\BulkActionGroup;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;

class PaymentsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Payments';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getCustomerPayments())
            ->emptyState(view('empty-state'))
            ->columns([
                TextColumn::make('id')
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('amount')
                    ->state(fn ($record) => $record['amount'] / 100)
                    ->badge()
                    ->money(fn ($record) => $record['currency'])
                    ->color(fn ($record) => match ($record['status']) {
                        'succeeded' => 'success',   // ✅ money received
                        default => 'gray',          // ❌ not yet cash
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'succeeded' => 'success',             // green
                        'processing' => 'warning',            // yellow
                        'requires_payment_method' => 'danger',// red
                        'requires_confirmation' => 'info',    // blue
                        'requires_action' => 'purple',        // purple accent
                        'requires_capture' => 'primary',      // stripe blue
                        'canceled' => 'gray',                 // neutral gray
                        default => 'secondary',
                    }),

                TextColumn::make('created')
                    ->since(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ApiErrorException
     * @throws NotFoundExceptionInterface
     */
    private function getCustomerPayments(): array
    {
        if (! session()->has('stripe.customer_id')) {
            return [];
        }

        $customer = session()->get('stripe.customer_id');

        return stripe()->paymentIntents->all([
            'customer' => $customer,
        ])->toArray()['data'];
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->resetTable();
    }
}
