<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Concerns\HasMoneyBadges;
use App\Filament\Widgets\BaseSchemaWidget;
use App\Filament\Widgets\Stripe\Concerns\InterpretsStripeAmounts;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InteractsWithStripeInvoices;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Stripe\Exception\ApiErrorException;

class LatestInvoiceInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;
    use HasStripeInvoiceForm;
    use InteractsWithStripeInvoices;
    use HasMoneyBadges;

    protected int|string|array $columnSpan = 'full';

    #[Computed(persist: true)]
    protected function latestInvoice(): array
    {
        $customerId = (string) $this->stripeContext()->customerId;

        if ($customerId === '') {
            return [];
        }

        try {
            return $this->latestStripeInvoice($customerId, [
                'expand' => [
                    'data.lines',
                    'data.payments',
                    'data.payments.data.payment'
                ],
            ]);
        } catch (ApiErrorException $e) {
            report($e);
            return [];
        }
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    protected function afterInvoiceFormHandled(): void
    {
        $this->refreshLatestInvoice();
    }

    #[On('stripe.invoices.refresh')]
    public function refreshLatestInvoice(): void
    {
        unset($this->latestInvoice, $this->stripePriceCollection, $this->stripeProductCollection);
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshLatestInvoice();
    }

    public function schema(Schema $schema): Schema
    {
        $invoice = $this->latestInvoice;
        $hostedUrl = Arr::get($invoice, 'hosted_invoice_url');

        return $schema
            ->state($invoice)
            ->components([
                Section::make('Latest Invoice')
                    ->columns(2)
                    ->headerActions([
                        $this->configureInvoiceFormAction(
                            Action::make('duplicateLatest')
                                ->label('Duplicate')
                                ->icon(Heroicon::OutlinedDocumentDuplicate)
                                ->outlined()
                                ->color(blank($invoice) ? 'gray' : 'primary')
                                ->disabled(blank($invoice))
                                ->modalHeading('Duplicate latest invoice')
                        )->fillForm(fn() => $this->getInvoiceFormDefaults(blank($invoice) ? null : $invoice)),
                        Action::make('sendLatest')
                            ->requiresConfirmation()
                            ->label('Send')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->color(blank($invoice) ? 'gray' : 'warning')
                            ->disabled(blank($invoice))
                            ->action(fn() => $this->sendHostedInvoiceLink($invoice)),
                        Action::make('openInvoice')
                            ->label('Open')
                            ->outlined()
                            ->color(blank($invoice) ? 'gray' : 'primary')
                            ->disabled(blank($invoice))
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url($hostedUrl)
                            ->openUrlInNewTab()
                            ->hidden(blank($hostedUrl)),
                        Action::make('reset')
                            ->action(fn() => $this->refreshLatestInvoice())
                            ->hiddenLabel()
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->link(),
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(?string $state) => match ($state) {
                                'draft' => 'gray',
                                'open' => 'warning',
                                'paid' => 'success',
                                'uncollectible' => 'danger',
                                'void' => 'gray',
                                default => 'secondary',
                            })
                            ->inlineLabel(),
                        TextEntry::make('created')
                            ->since()
                            ->inlineLabel(),
                        TextEntry::make('due_date')
                            ->since()
                            ->inlineLabel(),
                        TextEntry::make('total')
                            ->label('Total')
                            ->inlineLabel()
                            ->badge()
                            ->money(
                                currency: $this->moneyCurrency(
                                    fallback: fn () => data_get($this->latestInvoice, 'currency'),
                                ),
                                divideBy: $this->moneyDivideBy(),
                                locale: $this->moneyLocale(),
                                decimalPlaces: $this->moneyDecimalPlaces(),
                            ),
                        TextEntry::make('amount_paid')
                            ->label('Amount Paid')
                            ->inlineLabel()
                            ->badge()
                            ->money(
                                currency: $this->moneyCurrency(
                                    fallback: fn () => data_get($this->latestInvoice, 'currency'),
                                ),
                                divideBy: $this->moneyDivideBy(),
                                locale: $this->moneyLocale(),
                                decimalPlaces: $this->moneyDecimalPlaces(),
                            ),
                        TextEntry::make('amount_remaining')
                            ->label('Amount Remaining')
                            ->inlineLabel()
                            ->badge()
                            ->money(
                                currency: $this->moneyCurrency(
                                    fallback: fn () => data_get($this->latestInvoice, 'currency'),
                                ),
                                divideBy: $this->moneyDivideBy(),
                                locale: $this->moneyLocale(),
                                decimalPlaces: $this->moneyDecimalPlaces(),
                            ),
                        TextEntry::make('collection_method')
                            ->inlineLabel(),
                        RepeatableEntry::make('lines.data')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('product_id'),
                                TableColumn::make('price_id'),
                                TableColumn::make('description'),
                                TableColumn::make('pricing.unit_amount_decimal'),
                                TableColumn::make('quantity'),
                                TableColumn::make('amount'),
                            ])
                            ->schema([
                                TextEntry::make('pricing.price_details.product')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('pricing.price_details.price')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('description'),
                                TextEntry::make('pricing.unit_amount_decimal')
                                    ->badge()
                                    ->money(
                                        currency: $this->moneyCurrency(
                                            fallback: fn ($record) => data_get($record, 'pricing.currency')
                                                ?? data_get($this->latestInvoice, 'currency'),
                                        ),
                                        divideBy: $this->moneyDivideBy(),
                                        locale: $this->moneyLocale(),
                                        decimalPlaces: $this->moneyDecimalPlaces(),
                                    ),
                                TextEntry::make('quantity'),
                                TextEntry::make('amount')
                                    ->badge()
                                    ->money(
                                        currency: $this->moneyCurrency(
                                            fallback: fn ($record) => data_get($record, 'currency')
                                                ?? data_get($record, 'pricing.currency')
                                                ?? data_get($this->latestInvoice, 'currency'),
                                        ),
                                        divideBy: $this->moneyDivideBy(),
                                        locale: $this->moneyLocale(),
                                        decimalPlaces: $this->moneyDecimalPlaces(),
                                    ),
                            ]),
                        RepeatableEntry::make('payments.data')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('id'),
                                TableColumn::make('payment_intent_id'),
                                TableColumn::make('status'),
                                TableColumn::make('amount_paid'),
                                TableColumn::make('currency'),
                                TableColumn::make('created'),
                            ])
                            ->schema([
                                TextEntry::make('id')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable(),
                                TextEntry::make('payment.payment_intent')
                                    ->color('gray')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('amount_paid')
                                    ->badge()
                                    ->money(
                                        currency: $this->moneyCurrency([
                                            'currency',
                                            'payment.currency',
                                        ]),
                                        divideBy: $this->moneyDivideBy(
                                            currencyPath: [
                                                'currency',
                                                'payment.currency',
                                            ],
                                        ),
                                        locale: $this->moneyLocale(),
                                        decimalPlaces: $this->moneyDecimalPlaces(),
                                    ),
                                TextEntry::make('currency'),
                                TextEntry::make('created')
                                    ->since(),
                            ]),
                    ]),
            ]);
    }
}
