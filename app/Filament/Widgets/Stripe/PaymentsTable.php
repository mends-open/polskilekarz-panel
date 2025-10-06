<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Filament\Concerns\FormatsBadgeMoney;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeObject;

class PaymentsTable extends BaseTableWidget
{
    use InteractsWithDashboardContext;
    use FormatsBadgeMoney;

    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 3;

    protected static ?string $heading = 'Payments';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[On('reset')]
    public function resetComponent(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function refreshTable(): void
    {
        $this->resetComponent();
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $payments = collect($this->customerPayments());

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
            ->defaultPaginationPageOption(3)
            ->paginationPageOptions([3, 10, 25, 50])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')
                            ->color('gray')
                            ->badge(),
                    ])->space(2),
                    Stack::make([
                        $this->formatBadgeMoney(
                            TextColumn::make('amount'),
                            fn ($record) => $record['currency'],
                        )
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
    #[Computed(persist: true)]
    private function customerPayments(): array
    {
        $customerId = (string) $this->stripeContext()->customerId;

        if ($customerId === '') {
            return [];
        }

        $response = stripe()->paymentIntents->all([
            'customer' => $customerId,
            'limit' => 100,
        ]);

        $payments = [];

        foreach ($response->autoPagingIterator() as $payment) {
            $payments[] = $payment instanceof StripeObject
                ? $payment->toArray()
                : (array) $payment;
        }

        return $payments;
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->resetTable();
    }

}
