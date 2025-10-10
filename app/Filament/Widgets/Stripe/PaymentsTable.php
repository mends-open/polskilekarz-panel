<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\ResolvesStripeEnums;
use App\Filament\Widgets\Stripe\Enums\PaymentIntentStatus;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeObject;

class PaymentsTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use InteractsWithDashboardContext;
    use ResolvesStripeEnums;

    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 3;

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
        return $this->dashboardContextIsReady(
            fn (): bool => $this->chatwootContext()->hasContact(),
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.widgets.stripe.payments_table.heading'))
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
            ->emptyStateIcon(Heroicon::OutlinedCreditCard)
            ->emptyStateHeading(__('filament.widgets.stripe.payments_table.empty_state.heading'))
            ->emptyStateDescription(__('filament.widgets.stripe.payments_table.empty_state.description'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')
                            ->label(__('filament.widgets.stripe.payments_table.columns.id.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->color('gray')
                            ->badge(),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label(__('filament.widgets.stripe.payments_table.columns.amount.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->money(
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                            )
                            ->color(function ($record) {
                                $status = $this->makePaymentIntentStatus(data_get($record, 'status'));

                                return $status instanceof PaymentIntentStatus
                                    ? $status->getAmountBadgeColor()
                                    : 'gray';
                            }),
                        TextColumn::make('status')
                            ->label(__('filament.widgets.stripe.payments_table.columns.status.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->state(fn (?string $state) => $this->makePaymentIntentStatus($state) ?? $state)
                            ->color(fn ($state) => $state instanceof PaymentIntentStatus ? null : 'secondary'),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('payment_method.type')
                            ->label(__('filament.widgets.stripe.payments_table.columns.payment_method_type.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->state(function ($record) {
                                $type = data_get($record, 'payment_method.type');

                                return $type ? Str::upper($type) : null;
                            })
                            ->color('warning'),
                        TextColumn::make('presentment_details.presentment_amount')
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->money(
                                currency: fn ($record) => $record['presentment_details']['presentment_currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['presentment_details']['presentment_currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['presentment_details']['presentment_currency']),
                            ),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('created')
                            ->label(__('filament.widgets.stripe.payments_table.columns.created.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->since(),
                    ])->space(2),
                ]),
            ])
            ->filters([])
            ->headerActions([
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('openPaymentUrl')
                        ->url(fn ($record) => $record['charges']['data'][0]['receipt_url'] ?? null)
                        ->openUrlInNewTab()
                        ->label(__('filament.widgets.stripe.payments_table.actions.open_receipt.label'))
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->hidden(fn ($record) => blank(data_get($record, 'charges.data.0.receipt_url'))),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    /**
     * @throws ApiErrorException
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
            'expand' => ['data.payment_method'],
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
