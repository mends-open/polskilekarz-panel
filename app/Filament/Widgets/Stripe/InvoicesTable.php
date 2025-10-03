<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use App\Jobs\Stripe\CreateInvoice;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Dashboard\StripeContext;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Filament\Schemas\Components\Utilities\Get;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeObject;

class InvoicesTable extends BaseTableWidget
{
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Invoices';

    private ?array $customerInvoicesCache = null;

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->clearCustomerInvoicesCache();
    }

    private function refreshTable(): void
    {
        $this->resetComponent();
    }

    private function clearCustomerInvoicesCache(): void
    {
        $this->customerInvoicesCache = null;
    }

    #[Computed(persist: true)]
    public function stripePrices(): array
    {
        try {
            return stripe()->prices->all([
                'active' => true,
                'type' => 'one_time',
                'expand' => ['data.product'],
                'limit' => 100,
            ])->toArray()['data'] ?? [];
        } catch (ApiErrorException $exception) {
            Log::error('Failed to fetch Stripe prices.', [
                'exception' => $exception,
            ]);

            return [];
        }
    }

    private function getCreateInvoiceForm(): array
    {
        return [
            ToggleButtons::make('currency')
                ->label('Currency')
                ->options(fn (): array => $this->getCurrencyOptions())
                ->required()
                ->inline()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    $set('line_items', []);
                }),
            Repeater::make('line_items')
                ->label('Line items')
                ->default([])
                ->required()
                ->reorderable(false)
                ->visible(fn (Get $get): bool => filled($get('currency')))
                ->simple(
                    Select::make('price')
                        ->label('Product')
                        ->native(false)
                        ->searchable()
                        ->allowHtml()
                        ->options(fn (Get $get): array => $this->getPriceOptionsForCurrency($get('../../currency')))
                        ->disabled(fn (Get $get): bool => blank($get('../../currency')))
                        ->placeholder(fn (Get $get): string => $get('../../currency') ? 'Select a product' : 'Choose a currency first'),
                ),
        ];
    }

    private function getCurrencyOptions(): array
    {
        return $this->getStripePriceCollection()
            ->pluck('currency')
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $currency) => [$currency => Str::upper($currency)])
            ->all();
    }

    private function getPriceOptionsForCurrency(?string $currency): array
    {
        if (! $currency) {
            return [];
        }

        $currency = Str::lower($currency);

        return $this->getStripePriceCollection()
            ->filter(fn (array $price) => Str::lower($price['currency'] ?? '') === $currency)
            ->mapWithKeys(function (array $price): array {
                return [
                    $price['id'] => $this->formatPriceOptionLabel($price),
                ];
            })
            ->all();
    }

    private function getStripePriceCollection(): Collection
    {
        return collect($this->stripePrices())
            ->filter(fn (array $price): bool => (bool) data_get($price, 'product.active', true));
    }

    private function isSelectablePrice(?string $priceId): bool
    {
        if (! $priceId) {
            return false;
        }

        $price = $this->getStripePriceCollection()
            ->firstWhere('id', $priceId);

        if (! $price) {
            return false;
        }

        return (bool) data_get($price, 'product.active', true);
    }

    private function formatPriceOptionLabel(array $price): string
    {
        $description = $this->resolvePriceDescription($price);
        $amount = $this->formatPriceAmount($price);

        $schema = Schema::make($this);

        $descriptionComponent = Text::make($description)
            ->container($schema)
            ->grow()
            ->extraAttributes(['class' => 'text-left']);

        $amountComponent = Text::make($amount)
            ->container($schema)
            ->badge()
            ->color('primary')
            ->size(TextSize::Small)
            ->extraAttributes(['class' => 'whitespace-nowrap']);

        return sprintf(
            "<span class='%s'>%s%s</span>",
            'flex w-full items-center justify-between gap-3',
            $descriptionComponent->toHtml(),
            $amountComponent->toHtml(),
        );
    }

    private function resolvePriceDescription(array $price): string
    {
        $description = data_get($price, 'product.name')
            ?? data_get($price, 'nickname')
            ?? data_get($price, 'id');

        return (string) $description;
    }

    private function formatPriceAmount(array $price): string
    {
        $currency = Str::upper((string) data_get($price, 'currency', 'USD'));
        $amount = (int) data_get($price, 'unit_amount', 0);

        $divisor = $this->isZeroDecimalCurrency($currency) ? 1 : 100;

        return Number::currency($amount / $divisor, $currency);
    }

    private function isZeroDecimalCurrency(string $currency): bool
    {
        return in_array($currency, [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
            'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ], true);
    }

    private function handleCreateInvoice(array $data): void
    {
        $priceIds = collect($data['line_items'] ?? [])
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['price'] ?? null;
                }

                return is_string($item) ? $item : null;
            })
            ->filter(fn (?string $priceId) => $this->isSelectablePrice($priceId))
            ->values()
            ->all();

        if ($priceIds === []) {
            Notification::make()
                ->title('No products selected')
                ->body('Please select at least one product to include on the invoice.')
                ->danger()
                ->send();

            return;
        }

        $customerId = $this->ensureStripeCustomer();

        if (! $customerId) {
            return;
        }

        $currency = $data['currency'] ?? null;

        CreateInvoice::dispatch(
            customerId: $customerId,
            currency: $currency,
            priceIds: $priceIds,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Creating invoice')
            ->body('We are preparing the invoice in Stripe. You will be notified once it is ready.')
            ->info()
            ->send();

        $this->resetComponent();
    }

    private function getInvoiceFormDefaults(?array $invoice): array
    {
        $currency = null;
        $lineItems = [];

        if ($invoice) {
            $currencyOptions = $this->getCurrencyOptions();
            $currency = data_get($invoice, 'currency');
            $currency = is_string($currency) ? Str::lower($currency) : null;

            if (! $currency || ! array_key_exists($currency, $currencyOptions)) {
                $currency = null;
            }

            if ($currency) {
                $lineItems = collect(data_get($invoice, 'lines.data', []))
                    ->flatMap(function (array $line) use ($currency): array {
                        $priceId = data_get($line, 'price.id');
                        $priceCurrency = Str::lower((string) data_get($line, 'price.currency'));

                        if (! $priceId || $priceCurrency !== $currency || ! $this->isSelectablePrice($priceId)) {
                            return [];
                        }

                        $quantity = max(1, (int) data_get($line, 'quantity', 1));

                        return array_fill(0, $quantity, ['price' => $priceId]);
                    })
                    ->values()
                    ->all();
            }
        }

        return [
            'currency' => $currency,
            'line_items' => $lineItems,
        ];
    }

    private function ensureStripeCustomer(): ?string
    {
        $stripeContext = $this->stripeContext();

        if ($stripeContext->customerId) {
            return $stripeContext->customerId;
        }

        $chatwootContext = $this->chatwootContext();

        $accountId = $chatwootContext->accountId;
        $contactId = $chatwootContext->contactId;
        $impersonatorId = $chatwootContext->currentUserId;

        if (! $accountId || ! $contactId || ! $impersonatorId) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('We need a Chatwoot contact to create a Stripe customer. Please open this widget from a Chatwoot conversation.')
                ->danger()
                ->send();

            return null;
        }

        try {
            $contact = chatwoot()
                ->platform()
                ->impersonate($impersonatorId)
                ->contacts()
                ->get($accountId, $contactId)['payload'] ?? [];
        } catch (ConnectionException | RequestException $exception) {
            Log::error('Failed to fetch Chatwoot contact for Stripe customer creation.', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'exception' => $exception,
            ]);

            Notification::make()
                ->title('Failed to load Chatwoot contact')
                ->body('We were unable to load the Chatwoot contact details. Please try again.')
                ->danger()
                ->send();

            return null;
        }

        $payload = array_filter([
            'name' => data_get($contact, 'name'),
            'email' => data_get($contact, 'email'),
            'phone' => data_get($contact, 'phone_number'),
            'metadata' => [
                'chatwoot_account_id' => (string) $accountId,
                'chatwoot_contact_id' => (string) $contactId,
            ],
        ], fn ($value) => filled($value));

        try {
            $customer = stripe()->customers->create($payload);
        } catch (ApiErrorException $exception) {
            Log::error('Failed to create Stripe customer from Chatwoot contact.', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'payload' => $payload,
                'exception' => $exception,
            ]);

            Notification::make()
                ->title('Failed to create Stripe customer')
                ->body('We were unable to create a Stripe customer from the Chatwoot contact. Please try again.')
                ->danger()
                ->send();

            return null;
        }

        $this->dashboardContext()->storeStripe(new StripeContext($customer->id));

        Notification::make()
            ->title('Stripe customer created')
            ->body('A Stripe customer was created from the Chatwoot contact.')
            ->success()
            ->send();

        return $customer->id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getCustomerInvoices())
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
                            ->money(fn ($record) => $record['currency'], 100)
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
                    TextColumn::make('created')
                        ->since(),
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
                            ->money(fn ($record) => $record['currency'], 100)
                            ->badge(),
                    ]),
                ])->collapsible(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('create')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('success')
                    ->outlined()
                    ->modalIcon(Heroicon::OutlinedDocumentPlus)
                    ->modalHeading('Create invoice')
                    ->modalSubmitActionLabel('Create invoice')
                    ->form($this->getCreateInvoiceForm())
                    ->action(fn (array $data) => $this->handleCreateInvoice($data)),
                Action::make('duplicateLatest')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->outlined()
                    ->color(fn () => $this->hasCustomerInvoices() ? 'primary' : 'gray')
                    ->disabled(fn () => ! $this->hasCustomerInvoices())
                    ->modalIcon(Heroicon::OutlinedDocumentDuplicate)
                    ->modalHeading('Duplicate latest invoice')
                    ->modalSubmitActionLabel('Create invoice')
                    ->form($this->getCreateInvoiceForm())
                    ->fillForm(fn () => $this->getInvoiceFormDefaults($this->latestCustomerInvoice()))
                    ->action(fn (array $data) => $this->handleCreateInvoice($data)),
                Action::make('sendLatest')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->outlined()
                    ->requiresConfirmation()
                    ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                    ->modalHeading('Send latest invoice link?')
                    ->modalDescription('We will send the latest invoice link to the active Chatwoot conversation.')
                    ->color(fn () => $this->hasCustomerInvoices() ? 'warning' : 'gray')
                    ->disabled(fn () => ! $this->hasCustomerInvoices())
                    ->action(fn () => $this->sendLatestInvoice()),
                Action::make('reset')
                    ->action(fn () => $this->refreshTable())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('duplicateInvoice')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->form($this->getCreateInvoiceForm())
                        ->fillForm(function ($record): array {
                            if ($record instanceof StripeObject) {
                                $record = $this->normalizeStripeObject($record);
                            }

                            return $this->getInvoiceFormDefaults(is_array($record) ? $record : null);
                        })
                        ->action(fn (array $data) => $this->handleCreateInvoice($data)),
                    Action::make('sendInvoiceShortUrl')
                        ->action(fn ($record) => $this->sendShortUrl($record['hosted_invoice_url']))
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

    private function sendShortUrl(string $url): void
    {
        $context = $this->chatwootContext();

        $account = $context->accountId;
        $user = $context->currentUserId;
        $conversation = $context->conversationId;

        if (! $account || ! $user || ! $conversation) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('Unable to send the invoice link because the Chatwoot context is incomplete.')
                ->danger()
                ->send();

            return;
        }

        CreateInvoiceShortLink::dispatch(
            url: $url,
            accountId: $account,
            conversationId: $conversation,
            impersonatorId: $user,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Sending invoice link')
            ->body('We are preparing the invoice link and will send it to the conversation shortly.')
            ->info()
            ->send();

    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ApiErrorException
     */
    private function getCustomerInvoices(): array
    {
        if ($this->customerInvoicesCache !== null) {
            return $this->customerInvoicesCache;
        }

        $customerId = $this->stripeContext()->customerId;

        if (! $customerId) {
            return $this->customerInvoicesCache = [];
        }

        $response = stripe()->invoices->all([
            'customer' => $customerId,
        ]);

        return $this->customerInvoicesCache = collect($response->data ?? [])
            ->map(fn (mixed $invoice) => $this->normalizeStripeInvoice($invoice))
            ->all();
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->resetTable();
        $this->clearCustomerInvoicesCache();
    }

    private function sendLatestInvoice(): void
    {
        $latest = $this->latestCustomerInvoice();

        if (! $latest) {
            return;
        }

        $invoiceUrl = data_get($latest, 'hosted_invoice_url');

        if (blank($invoiceUrl)) {
            Notification::make()
                ->title('Invoice link unavailable')
                ->body('We could not find a hosted invoice URL on the latest invoice.')
                ->warning()
                ->send();

            return;
        }

        $this->sendShortUrl($invoiceUrl);
    }

    private function hasCustomerInvoices(): bool
    {
        return $this->getCustomerInvoices() !== [];
    }

    private function latestCustomerInvoice(): ?array
    {
        return collect($this->getCustomerInvoices())
            ->sortByDesc('created')
            ->first() ?: null;
    }

    /**
     * @param  StripeObject|array  $invoice
     */
    private function normalizeStripeInvoice(mixed $invoice): array
    {
        $normalized = $this->normalizeStripeObject($invoice);

        $normalized['lines']['data'] = collect(data_get($normalized, 'lines.data', []))
            ->map(function (array $line): array {
                $line['description'] = $line['description']
                    ?? data_get($line, 'price.product.name')
                    ?? data_get($line, 'price.nickname')
                    ?? data_get($line, 'price.id');

                $line['amount'] = $line['amount']
                    ?? data_get($line, 'amount_excluding_tax')
                    ?? data_get($line, 'price.unit_amount');

                return $line;
            })
            ->all();

        return $normalized;
    }

    private function normalizeStripeObject(mixed $value): array
    {
        if ($value instanceof StripeObject) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return [];
        }

        foreach ($value as $key => $item) {
            if ($item instanceof StripeObject || is_array($item)) {
                $value[$key] = $this->normalizeStripeObject($item);
            }
        }

        return $value;
    }

}
