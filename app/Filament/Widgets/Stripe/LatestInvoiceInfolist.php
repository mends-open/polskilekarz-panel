<?php

namespace App\Filament\Widgets\Stripe;

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
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Stripe\Exception\ApiErrorException;

class LatestInvoiceInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;
    use HasStripeInvoiceForm;
    use InteractsWithStripeInvoices;

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
                'expand' => ['data.lines', 'data.payments'],
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
        $invoice = $this->latestInvoice();
        $hasInvoice = filled($invoice);
        $hostedUrl = Arr::get($invoice, 'hosted_invoice_url');
        $state = $this->prepareInvoiceState($invoice);

        $components = $hasInvoice
            ? array_merge(
                $this->invoiceOverviewComponents(),
                [
                    $this->buildLineItemsComponent(),
                    $this->buildPaymentsComponent(),
                ],
            )
            : [
                TextEntry::make('empty_message')
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->color('gray')
                    ->columnSpanFull(),
            ];

        return $schema
            ->state($state)
            ->components([
                Section::make('Latest Invoice')
                    ->columns(3)
                    ->description($hasInvoice ? 'Stripe invoice snapshot with actionable payment, billing, and customer context.' : null)
                    ->headerActions([
                        $this->configureInvoiceFormAction(
                            Action::make('duplicateLatest')
                                ->label('Duplicate')
                                ->icon(Heroicon::OutlinedDocumentDuplicate)
                                ->outlined()
                                ->color($hasInvoice ? 'primary' : 'gray')
                                ->disabled(! $hasInvoice)
                                ->modalHeading('Duplicate latest invoice')
                        )->fillForm(fn() => $this->getInvoiceFormDefaults($hasInvoice ? $invoice : null)),

                        Action::make('sendLatest')
                            ->requiresConfirmation()
                            ->label('Send')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->color($hasInvoice ? 'warning' : 'gray')
                            ->disabled(! $hasInvoice)
                            ->action(fn() => $this->sendHostedInvoiceLink($invoice)),

                        Action::make('openInvoice')
                            ->label('Open')
                            ->outlined()
                            ->color($hasInvoice ? 'primary' : 'gray')
                            ->disabled(! $hasInvoice)
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
                    ->schema($components),
            ]);
    }

    private function prepareInvoiceState(?array $invoice): array
    {
        if (blank($invoice)) {
            return [
                'empty_message' => 'Select a Stripe customer to view their latest invoice details.',
            ];
        }

        return [
            'summary' => $this->buildRfcSummary($invoice),
            'financials' => $this->buildSolidFinancialSnapshot($invoice),
            'billing' => $this->buildBillingInsights($invoice),
            'customer' => $this->extractCustomerDetails($invoice),
            'line_items' => $this->formatInvoiceLineItems($invoice),
            'payments' => $this->buildKssiPaymentBreakdown($invoice),
        ];
    }

    /**
     * RFC (Rich Facts Capsule) – concise, high-signal invoice metadata.
     */
    private function buildRfcSummary(array $invoice): array
    {
        return [
            'id' => Arr::get($invoice, 'id'),
            'number' => Arr::get($invoice, 'number'),
            'status' => Arr::get($invoice, 'status'),
            'created' => $this->resolveTimestamp(Arr::get($invoice, 'created')),
            'due_date' => $this->resolveTimestamp(Arr::get($invoice, 'due_date')),
            'period_range' => $this->formatPeriod(
                Arr::get($invoice, 'period_start'),
                Arr::get($invoice, 'period_end')
            ),
            'billing_reason' => Arr::get($invoice, 'billing_reason'),
        ];
    }

    /**
     * SOLID (Structured Overview of Ledger & Invoice Data) – financial health snapshot.
     */
    private function buildSolidFinancialSnapshot(array $invoice): array
    {
        $currency = $this->resolveStripeCurrency($invoice);
        $discountAmount = collect(Arr::get($invoice, 'total_discount_amounts', []))
            ->filter(fn($discount) => is_array($discount) && is_numeric(Arr::get($discount, 'amount')))
            ->sum(fn($discount) => (int) Arr::get($discount, 'amount'));

        return [
            'currency' => Str::upper($currency ?? 'usd'),
            'subtotal' => $this->formatStripeMoney($invoice, 'subtotal', $invoice),
            'total' => $this->formatStripeMoney($invoice, 'total', $invoice),
            'amount_due' => $this->formatStripeMoney($invoice, 'amount_due', $invoice),
            'amount_paid' => $this->formatStripeMoney($invoice, 'amount_paid', $invoice),
            'amount_remaining' => $this->formatStripeMoney($invoice, 'amount_remaining', $invoice),
            'amount_shipping' => $this->formatStripeMoney($invoice, 'amount_shipping', $invoice),
            'total_discount' => $discountAmount > 0
                ? $this->formatCurrencyAmount(((float) $discountAmount) / 100, $currency)
                : null,
        ];
    }

    private function buildBillingInsights(array $invoice): array
    {
        return [
            'collection_method' => Arr::get($invoice, 'collection_method'),
            'collection_method_label' => $this->formatCollectionMethod(Arr::get($invoice, 'collection_method')),
            'auto_advance' => (bool) Arr::get($invoice, 'auto_advance'),
            'customer_tax_exempt' => Arr::get($invoice, 'customer_tax_exempt'),
            'account_name' => Arr::get($invoice, 'account_name'),
            'account_country' => Arr::get($invoice, 'account_country'),
        ];
    }

    private function extractCustomerDetails(array $invoice): array
    {
        return [
            'name' => Arr::get($invoice, 'customer_name'),
            'email' => Arr::get($invoice, 'customer_email'),
            'customer_id' => Arr::get($invoice, 'customer'),
        ];
    }

    /**
     * KSSI (Key Settlement Signals & Insights) – reconciled payment attempts.
     */
    private function buildKssiPaymentBreakdown(array $invoice): array
    {
        $currency = $this->resolveStripeCurrency($invoice);

        return collect(Arr::get($invoice, 'payments.data', []))
            ->filter(fn($payment) => is_array($payment))
            ->map(function (array $payment) use ($currency): array {
                return [
                    'id' => Arr::get($payment, 'id'),
                    'type' => Arr::get($payment, 'payment.type'),
                    'status' => Arr::get($payment, 'status'),
                    'amount' => $this->formatStripeMoney($payment, 'amount_paid', ['currency' => $currency]),
                    'currency' => Str::upper($this->resolveStripeCurrency($payment, $currency) ?? $currency),
                    'created' => $this->resolveTimestamp(Arr::get($payment, 'created')),
                    'paid_at' => $this->resolveTimestamp(Arr::get($payment, 'status_transitions.paid_at')),
                    'reference' => Arr::get($payment, 'payment.payment_intent'),
                    'is_default' => (bool) Arr::get($payment, 'is_default'),
                    'livemode' => (bool) Arr::get($payment, 'livemode'),
                ];
            })
            ->values()
            ->all();
    }

    private function invoiceOverviewComponents(): array
    {
        return [
            TextEntry::make('summary.id')
                ->label('Invoice ID')
                ->badge()
                ->copyable()
                ->color('gray'),
            TextEntry::make('summary.number')
                ->label('Number')
                ->badge()
                ->copyable()
                ->color('gray')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('summary.status')
                ->label('Status')
                ->badge()
                ->color(fn(?string $state) => match ($state) {
                    'draft' => 'gray',
                    'open' => 'info',
                    'paid' => 'success',
                    'uncollectible' => 'danger',
                    'void' => 'gray',
                    default => 'secondary',
                })
                ->formatStateUsing(fn(?string $state) => $state ? Str::headline($state) : null),
            TextEntry::make('summary.billing_reason')
                ->label('Billing Reason')
                ->badge()
                ->color('secondary')
                ->formatStateUsing(fn(?string $state) => $state ? Str::headline($state) : '—'),
            TextEntry::make('summary.created')
                ->label('Created')
                ->since(),
            TextEntry::make('summary.due_date')
                ->label('Due Date')
                ->date(),
            TextEntry::make('financials.total')
                ->label('Total')
                ->badge()
                ->color('primary')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.amount_paid')
                ->label('Paid')
                ->badge()
                ->color('success')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.amount_due')
                ->label('Amount Due')
                ->badge()
                ->color('warning')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.amount_remaining')
                ->label('Remaining')
                ->badge()
                ->color('danger')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.subtotal')
                ->label('Subtotal')
                ->badge()
                ->color('secondary')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.amount_shipping')
                ->label('Shipping')
                ->badge()
                ->color('secondary')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.total_discount')
                ->label('Discounts')
                ->badge()
                ->color('secondary')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('financials.currency')
                ->label('Currency')
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('billing.collection_method_label')
                ->label('Collection Method')
                ->badge()
                ->color('primary')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('billing.auto_advance')
                ->label('Auto Advance')
                ->badge()
                ->color(fn($state) => $state ? 'success' : 'secondary')
                ->formatStateUsing(fn($state) => $state ? 'Enabled' : 'Disabled'),
            TextEntry::make('billing.customer_tax_exempt')
                ->label('Customer Tax Exempt')
                ->badge()
                ->formatStateUsing(fn(?string $state) => $state ? Str::headline($state) : 'None'),
            TextEntry::make('billing.account_name')
                ->label('Account Name')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('billing.account_country')
                ->label('Account Country')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('customer.name')
                ->label('Customer')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('customer.email')
                ->label('Email')
                ->copyable()
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('customer.customer_id')
                ->label('Customer ID')
                ->badge()
                ->copyable()
                ->color('gray')
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
            TextEntry::make('summary.period_range')
                ->label('Service Period')
                ->columnSpanFull()
                ->formatStateUsing(fn(?string $state) => $state ?? '—'),
        ];
    }

    private function buildLineItemsComponent(): RepeatableEntry
    {
        return RepeatableEntry::make('line_items')
            ->label('Line Items')
            ->columnSpanFull()
            ->emptyLabel('No line items were found for this invoice.')
            ->table([
                TableColumn::make('description')->label('Description'),
                TableColumn::make('price_id')->label('Price ID'),
                TableColumn::make('product_id')->label('Product ID'),
                TableColumn::make('quantity')->label('Qty'),
                TableColumn::make('unit_amount')->label('Unit Price'),
                TableColumn::make('amount')->label('Total'),
                TableColumn::make('currency')->label('Currency'),
            ])
            ->schema([
                TextEntry::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                TextEntry::make('price_id')
                    ->label('Price ID')
                    ->badge()
                    ->copyable()
                    ->color('gray'),
                TextEntry::make('product_id')
                    ->label('Product ID')
                    ->badge()
                    ->copyable()
                    ->color('gray'),
                TextEntry::make('quantity')
                    ->label('Quantity')
                    ->badge(),
                TextEntry::make('unit_amount')
                    ->label('Unit Price')
                    ->badge()
                    ->color('primary'),
                TextEntry::make('amount')
                    ->label('Line Total')
                    ->badge()
                    ->color('primary'),
                TextEntry::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('period')
                    ->label('Service Window')
                    ->columnSpanFull()
                    ->formatStateUsing(fn(?string $state) => $state ?? '—'),
                TextEntry::make('proration')
                    ->label('Proration')
                    ->badge()
                    ->color(fn(?string $state) => $state === 'Yes' ? 'warning' : 'secondary'),
            ]);
    }

    private function buildPaymentsComponent(): RepeatableEntry
    {
        return RepeatableEntry::make('payments')
            ->label('Payments')
            ->columnSpanFull()
            ->emptyLabel('No payments have been recorded yet for this invoice.')
            ->table([
                TableColumn::make('id')->label('Payment'),
                TableColumn::make('type')->label('Type'),
                TableColumn::make('status')->label('Status'),
                TableColumn::make('amount')->label('Amount'),
                TableColumn::make('created')->label('Created'),
            ])
            ->schema([
                TextEntry::make('id')
                    ->label('Payment ID')
                    ->badge()
                    ->copyable()
                    ->color('gray'),
                TextEntry::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? Str::headline($state) : '—'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(?string $state) => $state ? Str::headline($state) : '—'),
                TextEntry::make('amount')
                    ->label('Amount')
                    ->badge()
                    ->color('primary'),
                TextEntry::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('created')
                    ->label('Created')
                    ->since(),
                TextEntry::make('paid_at')
                    ->label('Paid At')
                    ->since(),
                TextEntry::make('reference')
                    ->label('Reference')
                    ->badge()
                    ->copyable()
                    ->color('gray'),
                TextEntry::make('is_default')
                    ->label('Default Method')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'secondary')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),
                TextEntry::make('livemode')
                    ->label('Live Mode')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'secondary')
                    ->formatStateUsing(fn($state) => $state ? 'Live' : 'Test'),
            ]);
    }

    private function formatInvoiceLineItems(array $invoice): array
    {
        $currency = $this->resolveStripeCurrency($invoice);

        return collect(Arr::get($invoice, 'lines.data', []))
            ->filter(fn($line) => is_array($line))
            ->map(function (array $line) use ($invoice, $currency): array {
                return [
                    'description' => Arr::get($line, 'description') ?? '—',
                    'price_id' => Arr::get($line, 'pricing.price_details.price'),
                    'product_id' => Arr::get($line, 'pricing.price_details.product'),
                    'quantity' => Arr::get($line, 'quantity'),
                    'unit_amount' => $this->formatStripeMoney($line, 'pricing.unit_amount_decimal', $invoice),
                    'amount' => $this->formatStripeMoney($line, 'amount', $invoice),
                    'period' => $this->formatPeriod(
                        Arr::get($line, 'period.start'),
                        Arr::get($line, 'period.end')
                    ),
                    'proration' => Arr::get($line, 'parent.invoice_item_details.proration') ? 'Yes' : 'No',
                    'currency' => Str::upper($this->resolveStripeCurrency($line, $currency) ?? $currency),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveTimestamp($timestamp): ?Carbon
    {
        if (! is_numeric($timestamp)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp)
            ->setTimezone(config('app.timezone'));
    }

    private function formatPeriod($start, $end): ?string
    {
        if (! is_numeric($start) && ! is_numeric($end)) {
            return null;
        }

        $startDate = is_numeric($start) ? Carbon::createFromTimestamp((int) $start)->setTimezone(config('app.timezone')) : null;
        $endDate = is_numeric($end) ? Carbon::createFromTimestamp((int) $end)->setTimezone(config('app.timezone')) : null;

        if ($startDate && $endDate) {
            if ($startDate->isSameDay($endDate)) {
                return $startDate->toFormattedDayDateString();
            }

            return sprintf('%s — %s', $startDate->toFormattedDayDateString(), $endDate->toFormattedDayDateString());
        }

        $date = $startDate ?? $endDate;

        return $date?->toFormattedDayDateString();
    }

    private function formatCollectionMethod(?string $method): ?string
    {
        return match ($method) {
            'charge_automatically' => 'Charge automatically',
            'send_invoice' => 'Send invoice',
            null, '' => null,
            default => Str::headline($method),
        };
    }

    private function formatStripeMoney(?array $record, string $amountKey, ?array $currencyContext = null): ?string
    {
        $amount = $this->extractStripeAmount($record, $amountKey);

        if ($amount === null) {
            return null;
        }

        $currency = $this->resolveStripeCurrency($record, $this->resolveStripeCurrency($currencyContext));

        return $this->formatCurrencyAmount($amount, $currency);
    }

    private function formatCurrencyAmount(float $amount, ?string $currency): string
    {
        $currency = Str::upper($currency ?? 'USD');

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter(app()->getLocale() ?? 'en', \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($amount, $currency);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return sprintf('%s %s', $currency, number_format($amount, 2));
    }
}
