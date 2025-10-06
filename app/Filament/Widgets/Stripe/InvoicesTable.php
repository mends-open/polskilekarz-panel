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

    protected static ?string $heading = 'Invoices';

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
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
                            ->color('gray')
                            ->badge(),
                        TextColumn::make('number')
                            ->badge(),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('total')
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
                            ->state(fn ($record) => Str::upper($record['currency']))
                            ->badge(),
                    ]),
                    Stack::make([
                        TextColumn::make('created')
                            ->since(),
                    ]),
                ]),
                Panel::make([
                    Split::make([
                        TextColumn::make('lines.data.*.description')
                            ->listWithLineBreaks(),
                        TextColumn::make('lines.data.*.quantity')
                            ->prefix('x')
                            ->listWithLineBreaks(),
                        TextColumn::make('lines.data.*.amount')
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
                $this->configureInvoiceFormAction(
                    Action::make('create')
                        ->icon(Heroicon::OutlinedDocumentPlus)
                        ->color('success')
                        ->outlined()
                        ->visible(false)
                        ->modalIcon(Heroicon::OutlinedDocumentPlus)
                        ->modalHeading('Create invoice')
                ),
                $this->configureInvoiceFormAction(
                    Action::make('duplicateLatest')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->outlined()
                        ->visible(false)
                        ->color(fn () => $this->hasCustomerInvoices() ? 'primary' : 'gray')
                        ->disabled(fn () => ! $this->hasCustomerInvoices())
                        ->modalIcon(Heroicon::OutlinedDocumentDuplicate)
                        ->modalHeading('Duplicate latest invoice')
                )
                    ->fillForm(function () {
                        $invoice = $this->latestCustomerInvoice();

                        return $this->getInvoiceFormDefaults($invoice);
                    }),
                Action::make('sendLatest')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->outlined()
                    ->visible(false)
                    ->requiresConfirmation()
                    ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                    ->modalHeading('Send latest invoice link?')
                    ->modalDescription('We will send the latest invoice link to the active Chatwoot conversation.')
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
                            ->label('Duplicate')
                            ->icon(Heroicon::OutlinedDocumentDuplicate)
                    )
                        ->fillForm(function ($record): array {
                            if ($record instanceof StripeObject) {
                                $record = $this->normalizeStripeObject($record);
                            }

                            return $this->getInvoiceFormDefaults(is_array($record) ? $record : null);
                        }),
                    Action::make('sendInvoiceShortUrl')
                        ->action(fn ($record) => $this->sendInvoiceRecordLink($record))
                        ->label('Send')
                        ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                        ->modalHeading('Send invoice link?')
                        ->modalDescription('We will send this invoice link to the current Chatwoot conversation.'),
                    Action::make('openInvoiceUrl')
                        ->url(fn ($record) => $record['hosted_invoice_url'])
                        ->openUrlInNewTab()
                        ->label('Open')
                        ->icon(Heroicon::OutlinedEnvelopeOpen),
                ]),
            ])
            ->toolbarActions([]);
    }

    /**
     * @throws ApiErrorException
     */
    #[Computed(persist: true)]
    private function customerInvoices(): array
    {
        $customerId = $this->stripeContext()->customerId;

        if (! $customerId) {
            return [];
        }

        try {
            return $this->fetchStripeInvoices($customerId);
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

    /**
     * @throws ApiErrorException
     */
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

        return $invoice !== [] ? $invoice : null;
    }

    private function sendInvoiceRecordLink($record): void
    {
        if ($record instanceof StripeObject) {
            $record = $this->normalizeStripeObject($record);
        }

        if (! is_array($record)) {
            return;
        }

        $this->sendHostedInvoiceLink($record);
    }
}
