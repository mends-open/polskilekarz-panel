<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InteractsWithStripeInvoices;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Panel;
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

class InvoicesTable extends BaseTableWidget
{
    use HandlesCurrencyDecimals;
    use HasStripeInvoiceForm;
    use InteractsWithDashboardContext;
    use InteractsWithStripeInvoices;

    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 3;

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady(
            fn (): bool => $this->chatwootContext()->hasContact(),
        );
    }

    #[On('stripe.invoices.refresh')]
    public function refreshInvoices(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
        unset($this->customerInvoices);
        unset($this->stripePriceCollection, $this->stripeProductCollection);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.widgets.stripe.invoices_table.heading'))
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $invoices = collect($this->customerInvoices());

                $records = $invoices
                    ->forPage($page, $recordsPerPage)
                    ->values()
                    ->all();

                return new LengthAwarePaginator(
                    items: $records,
                    total: $invoices->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->defaultPaginationPageOption(3)
            ->extremePaginationLinks()
            ->paginationPageOptions([3, 10, 25, 50])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.id.label'))
                            ->color('gray')
                            ->badge(),
                        TextColumn::make('number')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.number.label'))
                            ->badge(),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('total')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.total.label'))
                            ->badge()
                            ->money(
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                            )
                            ->color(fn ($record) => match ($record['status']) {
                                'paid' => 'success',                     // ✅ money in
                                'open', 'draft', 'uncollectible' => 'danger', // ❌ not collected
                                'void' => 'gray',                        // ⚪ cancelled
                                default => 'secondary',
                            }),
                        TextColumn::make('status')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.status.label'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid' => 'success',        // green
                                'open' => 'info',           // blue
                                'draft' => 'secondary',     // neutral
                                'uncollectible' => 'danger',// red
                                'void' => 'gray',           // gray
                                default => 'secondary',
                            }),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('currency')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.currency.label'))
                            ->state(fn ($record) => Str::upper($record['currency']))
                            ->badge(),
                    ]),
                    Stack::make([
                        TextColumn::make('created')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.created.label'))
                            ->since(),
                    ]),
                ]),
                Panel::make([
                    Split::make([
                        TextColumn::make('lines.data.*.description')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.lines.description.label'))
                            ->listWithLineBreaks(),
                        TextColumn::make('lines.data.*.quantity')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.lines.quantity.label'))
                            ->prefix(__('filament.widgets.stripe.invoices_table.columns.lines.quantity.prefix'))
                            ->listWithLineBreaks(),
                        TextColumn::make('lines.data.*.amount')
                            ->label(__('filament.widgets.stripe.invoices_table.columns.lines.amount.label'))
                            ->listWithLineBreaks()
                            ->money(
                                currency: fn ($record) => $record['currency'],
                                divideBy: fn ($record) => $this->currencyDivisor($record['currency']),
                                locale: config('app.locale'),
                                decimalPlaces: fn ($record) => $this->currencyDecimalPlaces($record['currency']),
                            )
                            ->badge(),
                    ]),
                ])->collapsible(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('sendLatest')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->outlined()
                    ->visible(false)
                    ->requiresConfirmation()
                    ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                    ->modalHeading(__('filament.widgets.stripe.invoices_table.actions.send_latest.modal.heading'))
                    ->modalDescription(__('filament.widgets.stripe.invoices_table.actions.send_latest.modal.description'))
                    ->color(fn () => $this->hasCustomerInvoices() ? 'warning' : 'gray')
                    ->disabled(fn () => ! $this->hasCustomerInvoices())
                    ->action(fn () => $this->sendLatestInvoice()),
                Action::make('reset')
                    ->action(fn () => $this->refreshInvoices())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
            ])
            ->recordActions([
                ActionGroup::make([
                    $this->configureInvoiceFormAction(
                        Action::make('duplicateInvoice')
                            ->label(__('filament.widgets.stripe.invoices_table.actions.duplicate.label'))
                            ->icon(Heroicon::OutlinedDocumentDuplicate)
                    )
                        ->fillForm(function ($record): array {
                            if ($record instanceof StripeObject) {
                                $record = $record->toArray();
                            }

                            return $this->getInvoiceFormDefaults(is_array($record) ? $record : null);
                        }),
                    Action::make('sendInvoiceShortUrl')
                        ->action(fn ($record) => $this->sendInvoiceRecordLink($record))
                        ->label(__('filament.widgets.stripe.invoices_table.actions.send.label'))
                        ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                        ->modalHeading(__('filament.widgets.stripe.invoices_table.actions.send.modal.heading'))
                        ->modalDescription(__('filament.widgets.stripe.invoices_table.actions.send.modal.description')),
                    Action::make('openInvoiceUrl')
                        ->url(fn ($record) => $record['hosted_invoice_url'])
                        ->openUrlInNewTab()
                        ->label(__('filament.widgets.stripe.invoices_table.actions.open.label'))
                        ->icon(Heroicon::OutlinedEnvelopeOpen),
                ]),
            ])
            ->toolbarActions([]);
    }

    #[Computed(persist: true)]
    private function customerInvoices(): array
    {
        $customerId = $this->stripeContext()->customerId;

        if (! $customerId) {
            return [];
        }

        try {
            return array_map(
                fn (StripeObject $invoice) => $invoice->toArray(),
                $this->fetchStripeInvoices($customerId)
            );
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshInvoices();
    }

    protected function afterInvoiceFormHandled(): void
    {
        $this->refreshInvoices();
    }

    private function sendLatestInvoice(): void
    {
        $latest = $this->latestCustomerInvoice();

        if (! $latest) {
            return;
        }

        $this->sendHostedInvoiceLink($latest);
    }

    private function hasCustomerInvoices(): bool
    {
        return $this->customerInvoices() !== [];
    }

    private function latestCustomerInvoice(): ?array
    {
        try {
            $invoice = $this->latestStripeInvoice($this->stripeContext()->customerId);
        } catch (ApiErrorException $exception) {
            report($exception);

            return null;
        }

        if (! $invoice instanceof StripeObject) {
            return null;
        }

        return $invoice->toArray();
    }

    private function sendInvoiceRecordLink($record): void
    {
        if ($record instanceof StripeObject) {
            $record = $record->toArray();
        }

        if (! is_array($record)) {
            return;
        }

        $this->sendHostedInvoiceLink($record);
    }
}
