<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Jobs\Stripe\CreateInvoice;
use App\Support\Dashboard\StripeContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeObject;

trait HasStripeInvoiceForm
{
    protected function getStripePrices(): array
    {
        try {
            return stripe()->prices->all([
                'active' => true,
                'type' => 'one_time',
                'expand' => ['data.product'],
                'limit' => 100,
            ])->toArray()['data'] ?? [];
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }

    protected function getCreateInvoiceForm(): array
    {
        return [
            Repeater::make('line_items')
                ->compact()
                ->label('Products')
                ->reorderable(false)
                ->required()
                ->rules(['array', 'min:1'])
                ->minItems(1)
                ->validationAttribute('products')
                ->hiddenLabel()
                ->table([
                    TableColumn::make('Product')
                        ->width('45%'),
                    TableColumn::make('Price')
                        ->width('25%'),
                    TableColumn::make('Quantity')
                        ->width('10%'),
                    TableColumn::make('Subtotal')
                        ->width('20%'),
                ])
                ->schema([
                    Select::make('product')
                        ->label('Product')
                        ->options(fn (): array => $this->getProductOptions())
                        ->searchable()
                        ->debounce()
                        ->native(false)
                        ->required()
                        ->disabled(fn (Get $get): bool => is_string($get('price')) && $get('price') !== '')
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            $set('price', null);
                            $this->guardLineItemsCurrency($set, $get);
                        })
                        ->placeholder('Select a product'),
                    Select::make('price')
                        ->label('Price')
                        ->native(false)
                        ->searchable()
                        ->debounce()
                        ->required()
                        ->options(function (Get $get): array {
                            $lineItems = $this->resolveLineItemsState($get);

                            return $this->getPriceOptionsForRow(
                                $lineItems,
                                is_string($productId = $get('product')) ? $productId : null,
                            );
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            if (! is_string($value) || $value === '') {
                                return null;
                            }

                            $price = $this->resolvePrice($value);

                            if (! $price) {
                                return null;
                            }

                            return $this->formatPriceAmount($price);
                        })
                        ->disabled(fn (Get $get): bool => ! is_string($get('product')) || $get('product') === '')
                        ->afterStateUpdated(fn (Set $set, Get $get) => $this->guardLineItemsCurrency($set, $get))
                        ->placeholder('Select a price'),
                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->debounce()
                        ->required(),
                    TextEntry::make('subtotal')
                        ->label('Subtotal')
                        ->state(function (Get $get): string {
                            $priceState = $get('price');
                            $priceId = is_string($priceState) ? $priceState : null;
                            $quantity = $this->normalizeQuantity($get('quantity'));

                            return $this->formatLineItemSubtotal($priceId, $quantity);
                        }),
                ]),
        ];
    }

    protected function getProductOptions(): array
    {
        return $this->stripeProductCollection()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (array $product, string $productId): array => [
                $productId => $product['name'],
            ])
            ->all();
    }

    protected function getPriceOptionsForRow(array $lineItems, ?string $productId): array
    {
        $lockedCurrency = $this->lockedCurrency($lineItems);

        $products = $this->stripeProductCollection();

        if ($productId) {
            $product = $products->get($productId);

            if (! $product) {
                return [];
            }

            $prices = $product['prices'] instanceof Collection
                ? $product['prices']
                : collect($product['prices']);
        } else {
            $prices = $this->stripePriceCollection()->values();
        }

        return $prices
            ->filter(fn (array $price): bool => is_string(data_get($price, 'id')) && data_get($price, 'id') !== '')
            ->filter(function (array $price) use ($lockedCurrency): bool {
                if (! $lockedCurrency) {
                    return true;
                }

                return $this->priceCurrency($price['id']) === $lockedCurrency;
            })
            ->mapWithKeys(fn (array $price): array => [
                $price['id'] => $this->formatPriceAmount($price),
            ])
            ->all();
    }

    #[Computed(persist: true)]
    protected function stripePriceCollection(): Collection
    {
        return collect($this->getStripePrices())
            ->filter(fn (array $price): bool => (bool) data_get($price, 'active', true))
            ->filter(fn (array $price): bool => (bool) data_get($price, 'product.active', true))
            ->filter(fn (array $price): bool => is_string(data_get($price, 'id')) && data_get($price, 'id') !== '')
            ->map(function (array $price): array {
                $price['currency'] = Str::lower((string) data_get($price, 'currency'));
                $price['product_id'] = $this->resolveProductId($price);

                return $price;
            })
            ->keyBy('id');
    }

    #[Computed(persist: true)]
    protected function stripeProductCollection(): Collection
    {
        return $this->stripePriceCollection()
            ->groupBy(fn (array $price): ?string => $price['product_id'] ?? null)
            ->filter(fn (Collection $prices, ?string $productId): bool => is_string($productId) && $productId !== '')
            ->mapWithKeys(function (Collection $prices, string $productId): array {
                $first = $prices->first();

                return [
                    $productId => [
                        'id' => $productId,
                        'name' => $this->resolveProductName(is_array($first) ? $first : []),
                        'default_price_id' => $this->resolveProductDefaultPriceId(is_array($first) ? $first : []),
                        'prices' => $prices->values(),
                    ],
                ];
            });
    }

    protected function resolvePrice(?string $priceId): ?array
    {
        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        return $this->stripePriceCollection()->get($priceId);
    }

    protected function priceCurrency(?string $priceId): ?string
    {
        $price = $this->resolvePrice($priceId);

        if (! $price) {
            return null;
        }

        $currency = data_get($price, 'currency');

        return is_string($currency) && $currency !== ''
            ? Str::lower($currency)
            : null;
    }

    protected function resolvePriceProductId(?string $priceId): ?string
    {
        $price = $this->resolvePrice($priceId);

        if (! $price) {
            return null;
        }

        return $this->resolveProductId($price);
    }

    protected function resolveProductId(array $price): ?string
    {
        foreach ([
            'product.id',
            'product',
            'product_id',
        ] as $key) {
            $value = data_get($price, $key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function resolveProductName(array $price): string
    {
        foreach ([
            data_get($price, 'product.name'),
            data_get($price, 'nickname'),
            data_get($price, 'product.id'),
            data_get($price, 'id'),
        ] as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'Product';
    }

    protected function resolveProductDefaultPriceId(array $price): ?string
    {
        foreach ([
            'product.default_price.id',
            'product.default_price',
            'default_price.id',
            'default_price',
        ] as $key) {
            $value = data_get($price, $key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function applyDefaultPrices(array $lineItems): array
    {
        $products = $this->stripeProductCollection();

        foreach ($lineItems as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $priceId = data_get($item, 'price');

            if (is_string($priceId) && $priceId !== '') {
                continue;
            }

            $productId = data_get($item, 'product');

            if (! is_string($productId) || $productId === '') {
                continue;
            }

            $product = $products->get($productId);

            if (! is_array($product)) {
                continue;
            }

            $allowedCurrency = $this->preferredCurrencyForRow($lineItems, $index);
            $preferredPriceId = $this->determineDefaultPriceForProduct($product, $allowedCurrency);

            if (! $preferredPriceId) {
                continue;
            }

            $lineItems[$index]['price'] = $preferredPriceId;
        }

        return $lineItems;
    }

    protected function preferredCurrencyForRow(array $lineItems, int $currentIndex): ?string
    {
        $firstLineCurrency = $this->firstLineCurrency($lineItems);

        if ($firstLineCurrency && $currentIndex !== 0) {
            return $firstLineCurrency;
        }

        foreach ($lineItems as $index => $item) {
            if ($index === $currentIndex) {
                continue;
            }

            $priceId = is_array($item) ? data_get($item, 'price') : null;

            if (! is_string($priceId) || $priceId === '') {
                continue;
            }

            $currency = $this->priceCurrency($priceId);

            if ($currency) {
                return $currency;
            }
        }

        return null;
    }

    protected function determineDefaultPriceForProduct(array $product, ?string $allowedCurrency): ?string
    {
        $prices = $product['prices'] instanceof Collection
            ? $product['prices']
            : collect($product['prices']);

        $prices = $prices
            ->filter(fn ($price): bool => is_array($price))
            ->filter(fn (array $price): bool => is_string(data_get($price, 'id')) && data_get($price, 'id') !== '')
            ->values();

        if ($prices->isEmpty()) {
            return null;
        }

        $defaultPriceId = data_get($product, 'default_price_id');

        if (is_string($defaultPriceId) && $defaultPriceId !== '') {
            $defaultPrice = $prices->firstWhere('id', $defaultPriceId);

            if ($defaultPrice && (! $allowedCurrency || $this->priceCurrency($defaultPriceId) === $allowedCurrency)) {
                return $defaultPriceId;
            }
        }

        if ($allowedCurrency) {
            $currencyPrices = $prices->filter(fn (array $price): bool => $this->priceCurrency($price['id']) === $allowedCurrency);

            if ($currencyPrices->isNotEmpty()) {
                return $currencyPrices
                    ->sortByDesc(fn (array $price): int => (int) data_get($price, 'unit_amount', 0))
                    ->first()['id'] ?? null;
            }
        }

        return $prices
            ->sortByDesc(fn (array $price): int => (int) data_get($price, 'unit_amount', 0))
            ->first()['id'] ?? null;
    }

    protected function formatLineItemSubtotal(?string $priceId, int $quantity): string
    {
        if (! $priceId) {
            return '—';
        }

        $price = $this->resolvePrice($priceId);

        if (! $price) {
            return '—';
        }

        $currency = (string) data_get($price, 'currency');
        $unitAmount = (int) data_get($price, 'unit_amount', 0);

        return $this->formatCurrencyAmount($unitAmount * max(1, $quantity), $currency);
    }

    protected function formatPriceAmount(array $price): string
    {
        $currency = (string) data_get($price, 'currency');
        $amount = (int) data_get($price, 'unit_amount', 0);

        return $this->formatCurrencyAmount($amount, $currency);
    }

    protected function formatCurrencyAmount(int $amount, string $currency): string
    {
        $currency = Str::upper($currency);

        if ($currency === '') {
            return '—';
        }

        $decimals = StripeCurrency::isZeroDecimal($currency) ? 0 : 2;
        $divisor = $decimals === 0 ? 1 : 100;
        $value = $amount / $divisor;
        $formatted = number_format($value, $decimals, '.', ' ');

        return sprintf('%s %s', $formatted, $currency);
    }

    protected function sanitizeLineItemsForState(array $lineItems): array
    {
        return collect($lineItems)
            ->map(fn ($item): Fluent => $this->mapSanitizedLineItem($item))
            ->filter(fn (Fluent $item): bool => filled($item->get('product')) || filled($item->get('price')))
            ->values()
            ->map(fn (Fluent $item): array => $item->toArray())
            ->all();
    }

    protected function mapSanitizedLineItem(mixed $item): Fluent
    {
        $state = is_array($item) ? $item : [];

        $product = data_get($state, 'product');
        $price = data_get($state, 'price');

        if (! is_string($product) || $product === '') {
            $product = $this->resolvePriceProductId(is_string($price) ? $price : null);
        }

        if (! is_string($price) || $price === '') {
            $price = $this->resolveProductDefaultPriceId($this->stripeProductCollection()->get($product) ?? []);
        }

        return new Fluent([
            'product' => $product,
            'price' => $price,
            'quantity' => $this->normalizeQuantity(data_get($state, 'quantity')),
        ]);
    }

    protected function normalizeLineItemsState(array $lineItems): array
    {
        $lineItems = $this->sanitizeLineItemsForState($lineItems);
        $lineItems = $this->applyDefaultPrices($lineItems);

        return $this->enforceCurrencyLock($lineItems);
    }

    protected function normalizeQuantity(mixed $quantity): int
    {
        if (is_numeric($quantity)) {
            return max(1, (int) $quantity);
        }

        return 1;
    }

    protected function firstLineCurrency(array $lineItems): ?string
    {
        $first = $lineItems[0] ?? null;

        if (! is_array($first)) {
            return null;
        }

        $priceId = data_get($first, 'price');

        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        return $this->priceCurrency($priceId);
    }

    protected function lockedCurrency(array $lineItems): ?string
    {
        $lineItems = $this->sanitizeLineItemsForState($lineItems);

        $currencies = collect($lineItems)
            ->map(fn (array $item): ?string => $this->priceCurrency($item['price'] ?? null))
            ->filter()
            ->unique();

        if ($currencies->count() > 1) {
            return null;
        }

        return $currencies->first() ?: null;
    }

    protected function enforceCurrencyLock(array $lineItems): array
    {
        $lockedCurrency = $this->lockedCurrency($lineItems);

        if (! $lockedCurrency) {
            return $lineItems;
        }

        return collect($lineItems)
            ->map(function (array $item) use ($lockedCurrency): array {
                $priceId = data_get($item, 'price');

                if (! is_string($priceId) || $priceId === '') {
                    return $item;
                }

                if ($this->priceCurrency($priceId) === $lockedCurrency) {
                    return $item;
                }

                $item['price'] = null;

                return $item;
            })
            ->all();
    }

    protected function extractInvoiceLineItems(array $lineItems): array
    {
        $lineItems = collect($lineItems)
            ->map(fn (mixed $item) => is_array($item) ? $item : [])
            ->filter(fn (array $item): bool => is_string($item['price'] ?? null) && $item['price'] !== '')
            ->map(fn (array $item): array => [
                'price' => $item['price'],
                'quantity' => $this->normalizeQuantity($item['quantity'] ?? null),
            ])
            ->values()
            ->all();

        return array_values($lineItems);
    }

    protected function determineCurrencyFromPriceIds(array $priceIds): ?string
    {
        $currencies = collect($priceIds)
            ->map(fn (?string $priceId): ?string => $this->priceCurrency($priceId))
            ->filter()
            ->unique();

        if ($currencies->count() !== 1) {
            return null;
        }

        return $currencies->first();
    }

    protected function resolveLineItemsState(Get $get): array
    {
        $lineItems = $get('line_items', isAbsolute: true);

        if (! is_array($lineItems)) {
            $lineItems = $get('../../line_items');
        }

        return $this->normalizeLineItemsState(is_array($lineItems) ? $lineItems : []);
    }

    protected function updateLineItemsState(Set $set, array $lineItems): void
    {
        $set('../../line_items', $lineItems, shouldCallUpdatedHooks: true);
        $set('line_items', $lineItems, isAbsolute: true, shouldCallUpdatedHooks: true);
    }

    protected function guardLineItemsCurrency(Set $set, Get $get): void
    {
        $lineItems = $get('line_items', isAbsolute: true);

        if (! is_array($lineItems)) {
            $lineItems = $get('../../line_items');
        }

        $lineItems = is_array($lineItems) ? $lineItems : [];

        $normalized = $this->normalizeLineItemsState($lineItems);

        if ($lineItems !== $normalized) {
            $this->updateLineItemsState($set, $normalized);
        }
    }

    protected function prepareInvoiceFormData(array $data): array
    {
        $lineItems = is_array($data['line_items'] ?? null) ? $data['line_items'] : [];

        $data['line_items'] = $this->normalizeLineItemsState($lineItems);

        return $data;
    }

    protected function configureInvoiceFormAction(Action $action): Action
    {
        return $action
            ->modalSubmitActionLabel('Create invoice')
            ->schema($this->getCreateInvoiceForm())
            ->mutateDataUsing(fn (array $data): array => $this->prepareInvoiceFormData($data))
            ->action(fn (array $data) => $this->handleCreateInvoice($data));
    }

    /**
     * @throws ApiErrorException
     */
    protected function handleCreateInvoice(array $data): void
    {
        $data = $this->prepareInvoiceFormData($data);

        $lineItems = $this->extractInvoiceLineItems($data['line_items'] ?? []);

        if ($lineItems === []) {
            Notification::make()
                ->title('No products selected')
                ->body('Please select at least one product and price to include on the invoice.')
                ->danger()
                ->send();

            return;
        }

        $priceIds = array_map(fn (array $item): string => $item['price'], $lineItems);
        $currency = $this->determineCurrencyFromPriceIds($priceIds);

        if ($currency === null) {
            Notification::make()
                ->title('Mixed currencies selected')
                ->body('All selected products must use the same currency. Please adjust your selection and try again.')
                ->danger()
                ->send();

            return;
        }

        $customerId = $this->ensureStripeCustomer();

        if (! $customerId) {
            return;
        }

        CreateInvoice::dispatch(
            customerId: $customerId,
            currency: $currency,
            lineItems: $lineItems,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Creating invoice')
            ->body('We are preparing the invoice in Stripe. You will be notified once it is ready.')
            ->info()
            ->send();

        $this->dispatch('stripe.invoices.refresh');

        $this->afterInvoiceFormHandled();
    }

    protected function getInvoiceFormDefaults(?array $invoice): array
    {
        $lineItems = [];

        if ($invoice) {
            $invoiceId = data_get($invoice, 'id');
            $lines = collect();

            if (is_string($invoiceId)) {
                $lines = collect($this->fetchInvoiceLineItems($invoiceId));
            }

            if ($lines->isEmpty()) {
                $lines = collect(data_get($invoice, 'lines.data', []));
            }

            $lineItems = $lines
                ->map(fn ($line) => $line instanceof StripeObject ? $line->toArray() : (array) $line)
                ->map(function (array $line) {
                    $priceId = $this->resolveLineItemPrice($line);

                    return [
                        'price' => $priceId,
                        'quantity' => $this->normalizeQuantity(data_get($line, 'quantity')),
                    ];
                })
                ->filter(fn (array $line) => is_string($line['price']) && $line['price'] !== '')
                ->groupBy('price')
                ->map(function (Collection $items, string $priceId): array {
                    return [
                        'price' => $priceId,
                        'quantity' => max(1, (int) $items->sum('quantity')),
                    ];
                })
                ->values()
                ->all();
        }

        $lineItems = $this->normalizeLineItemsState($lineItems);

        return [
            'line_items' => $lineItems,
        ];
    }

    protected function fetchInvoiceLineItems(string $invoiceId): array
    {
        try {
            $lineItems = stripe()->invoices->allLines($invoiceId, [
                'expand' => ['data.price', 'data.price.product'],
                'limit' => 100,
            ]);
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }

        $normalized = $this->normalizeStripeObject($lineItems);

        $lines = data_get($normalized, 'data', []);

        if (! is_array($lines)) {
            $lines = [];
        }

        return $lines;
    }

    protected function resolveLineItemPrice(array $line): ?string
    {
        foreach ([
            data_get($line, 'price.id'),
            data_get($line, 'price'),
            data_get($line, 'price_id'),
            data_get($line, 'pricing.price_details.price'),
            data_get($line, 'pricing.price_details.price.id'),
        ] as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @throws ApiErrorException
     */
    protected function ensureStripeCustomer(): ?string
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
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

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
            report($exception);

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

    protected function afterInvoiceFormHandled(): void
    {
        // Allow consuming components to hook into invoice creation.
    }
}
