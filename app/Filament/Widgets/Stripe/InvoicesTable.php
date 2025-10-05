<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use App\Jobs\Stripe\CreateInvoice;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Dashboard\StripeContext;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
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

    private ?Collection $stripePriceCollectionCache = null;

    private ?Collection $stripeProductCollectionCache = null;

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
            report($exception);

            return [];
        }
    }

    private function getCreateInvoiceForm(): array
    {
        return [
            Repeater::make('line_items')
                ->label('Products')
                ->reorderable(false)
                ->required()
                ->rules(['array', 'min:1'])
                ->minItems(1)
                ->default([$this->blankLineItem()])
                ->validationAttribute('products')
                ->table([
                    TableColumn::make('Product'),
                    TableColumn::make('Price'),
                    TableColumn::make('Quantity'),
                    TableColumn::make('Subtotal'),
                ])
                ->schema([
                    Select::make('product')
                        ->label('Product')
                        ->options(fn (): array => $this->getProductOptions())
                        ->searchable()
                        ->live()
                        ->native(false)
                        ->required()
                        ->disabled(fn (Get $get): bool => is_string($get('price')) && $get('price') !== '')
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            $set('price', null, shouldCallUpdatedHooks: false);
                            $this->guardLineItemsCurrency($set, $get);
                        })
                        ->placeholder('Select a product'),
                    Select::make('price')
                        ->label('Price')
                        ->native(false)
                        ->searchable()
                        ->live()
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
                        ->minValue(1)
                        ->default(1)
                        ->live()
                        ->required()
                        ->rules(['integer', 'min:1'])
                        ->placeholder('1'),
                    Placeholder::make('subtotal')
                        ->label('Subtotal')
                        ->content(function (Get $get): string {
                            $priceState = $get('price');
                            $priceId = is_string($priceState) ? $priceState : null;
                            $quantity = $this->normalizeQuantity($get('quantity'));

                            return $this->formatLineItemSubtotal($priceId, $quantity);
                        }),
                ]),
        ];
    }

    private function blankLineItem(): array
    {
        return [
            'product' => null,
            'price' => null,
            'quantity' => 1,
        ];
    }

    private function getProductOptions(): array
    {
        return $this->stripeProductCollection()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (array $product, string $productId): array => [
                $productId => $product['name'],
            ])
            ->all();
    }

    private function getPriceOptionsForRow(array $lineItems, ?string $productId): array
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

    private function stripePriceCollection(): Collection
    {
        if ($this->stripePriceCollectionCache instanceof Collection) {
            return $this->stripePriceCollectionCache;
        }

        return $this->stripePriceCollectionCache = collect($this->stripePrices())
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

    private function stripeProductCollection(): Collection
    {
        if ($this->stripeProductCollectionCache instanceof Collection) {
            return $this->stripeProductCollectionCache;
        }

        return $this->stripeProductCollectionCache = $this->stripePriceCollection()
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

    private function resolvePrice(?string $priceId): ?array
    {
        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        return $this->stripePriceCollection()->get($priceId);
    }

    private function priceCurrency(?string $priceId): ?string
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

    private function resolvePriceProductId(?string $priceId): ?string
    {
        $price = $this->resolvePrice($priceId);

        if (! $price) {
            return null;
        }

        return $this->resolveProductId($price);
    }

    private function resolveProductId(array $price): ?string
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

    private function resolveProductName(array $price): string
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

    private function resolveProductDefaultPriceId(array $price): ?string
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

    private function applyDefaultPrices(array $lineItems): array
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

    private function preferredCurrencyForRow(array $lineItems, int $currentIndex): ?string
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

    private function determineDefaultPriceForProduct(array $product, ?string $allowedCurrency): ?string
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

    private function formatLineItemSubtotal(?string $priceId, int $quantity): string
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

        $amount = $this->formatCurrencyAmount($unitAmount * max(1, $quantity), $currency);

        return $amount;
    }

    private function formatPriceAmount(array $price): string
    {
        $currency = (string) data_get($price, 'currency');
        $amount = (int) data_get($price, 'unit_amount', 0);

        return $this->formatCurrencyAmount($amount, $currency);
    }

    private function formatCurrencyAmount(int $amount, string $currency): string
    {
        $currency = Str::upper($currency);

        if ($currency === '') {
            return '—';
        }

        $decimals = $this->isZeroDecimalCurrency($currency) ? 0 : 2;
        $divisor = $decimals === 0 ? 1 : 100;
        $value = $amount / $divisor;
        $formatted = number_format($value, $decimals, '.', ' ');

        return sprintf('%s %s', $formatted, $currency);
    }

    private function isZeroDecimalCurrency(string $currency): bool
    {
        return in_array($currency, [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
            'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ], true);
    }

    private function sanitizeLineItemsForState(array $lineItems): array
    {
        return collect($lineItems)
            ->map(function ($item): array {
                $product = is_array($item) ? data_get($item, 'product') : null;
                $price = is_array($item) ? data_get($item, 'price') : null;
                $quantity = $this->normalizeQuantity(is_array($item) ? data_get($item, 'quantity') : null);

                $product = is_string($product) && $product !== '' ? $product : null;
                $price = is_string($price) && $price !== '' ? $price : null;

                if ($price) {
                    $resolvedProduct = $this->resolvePriceProductId($price);

                    if ($resolvedProduct) {
                        $product = $resolvedProduct;
                    }
                }

                return [
                    'product' => $product,
                    'price' => $price,
                    'quantity' => $quantity,
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeLineItemsState(array $lineItems): array
    {
        $lineItems = $this->sanitizeLineItemsForState($lineItems);
        $lineItems = $this->applyDefaultPrices($lineItems);

        return $this->enforceCurrencyLock($lineItems);
    }

    private function normalizeQuantity(mixed $quantity): int
    {
        if (is_numeric($quantity)) {
            $quantity = (int) $quantity;
        } else {
            $quantity = 1;
        }

        return $quantity > 0 ? $quantity : 1;
    }

    private function firstLineCurrency(array $lineItems): ?string
    {
        $firstLine = $lineItems[0] ?? null;

        if (! is_array($firstLine)) {
            return null;
        }

        $priceId = data_get($firstLine, 'price');

        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        return $this->priceCurrency($priceId);
    }

    private function lockedCurrency(array $lineItems): ?string
    {
        $selectedPrices = collect($lineItems)
            ->map(function ($item) {
                $priceId = is_array($item) ? data_get($item, 'price') : null;

                return is_string($priceId) && $priceId !== '' ? $priceId : null;
            })
            ->filter();

        if ($selectedPrices->count() < 2) {
            return null;
        }

        $firstLineCurrency = $this->firstLineCurrency($lineItems);

        if ($firstLineCurrency) {
            return $firstLineCurrency;
        }

        foreach ($selectedPrices as $priceId) {
            $currency = $this->priceCurrency($priceId);

            if ($currency) {
                return $currency;
            }
        }

        return null;
    }

    private function enforceCurrencyLock(array $lineItems): array
    {
        $firstLineCurrency = $this->firstLineCurrency($lineItems);
        $lockedCurrency = $this->lockedCurrency($lineItems);

        if (! $lockedCurrency) {
            return $lineItems;
        }

        return collect($lineItems)
            ->map(function (array $item, int $index) use ($lockedCurrency, $firstLineCurrency): array {
                $priceId = data_get($item, 'price');

                if (! is_string($priceId) || $priceId === '') {
                    return $item;
                }

                $priceCurrency = $this->priceCurrency($priceId);

                if (! $priceCurrency) {
                    return $item;
                }

                if ($index === 0 && $firstLineCurrency) {
                    return $item;
                }

                if ($priceCurrency !== $lockedCurrency) {
                    $item['price'] = null;
                }

                return $item;
            })
            ->values()
            ->all();
    }

    private function extractInvoiceLineItems(array $lineItems): array
    {
        $lineItems = $this->normalizeLineItemsState($lineItems);

        return collect($lineItems)
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $priceId = data_get($item, 'price');
                $priceId = is_string($priceId) && $priceId !== '' ? $priceId : null;

                if (! $priceId) {
                    return null;
                }

                return [
                    'price' => $priceId,
                    'quantity' => $this->normalizeQuantity(data_get($item, 'quantity')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function determineCurrencyFromPriceIds(array $priceIds): ?string
    {
        $currencies = collect($priceIds)
            ->map(fn (string $priceId) => $this->priceCurrency($priceId))
            ->filter()
            ->unique();

        if ($currencies->count() > 1) {
            return null;
        }

        return $currencies->first() ?: null;
    }

    private function resolveLineItemsState(Get $get): array
    {
        $lineItems = $get('../../line_items') ?? $get('line_items');

        $lineItems = is_array($lineItems) ? $lineItems : [];

        return $this->normalizeLineItemsState($lineItems);
    }

    private function updateLineItemsState(Set $set, array $lineItems): void
    {
        $set('../../line_items', $lineItems, shouldCallUpdatedHooks: false);
        $set('line_items', $lineItems, shouldCallUpdatedHooks: false);
    }

    private function guardLineItemsCurrency(Set $set, Get $get): void
    {
        $lineItems = $get('../../line_items') ?? $get('line_items');
        $lineItems = is_array($lineItems) ? $lineItems : [];

        $normalized = $this->normalizeLineItemsState($lineItems);

        if ($lineItems !== $normalized) {
            $this->updateLineItemsState($set, $normalized);
        }
    }

    private function prepareInvoiceFormData(array $data): array
    {
        $lineItems = is_array($data['line_items'] ?? null) ? $data['line_items'] : [];

        $data['line_items'] = $this->normalizeLineItemsState($lineItems);

        if ($data['line_items'] === []) {
            $data['line_items'][] = $this->blankLineItem();
        }

        return $data;
    }

    private function configureInvoiceFormAction(Action $action): Action
    {
        return $action
            ->modalWidth('7xl')
            ->extraModalWindowAttributes(['class' => 'sm:min-h-[70vh] sm:max-h-[90vh]'])
            ->modalSubmitActionLabel('Create invoice')
            ->form($this->getCreateInvoiceForm())
            ->mutateFormDataUsing(fn (array $data): array => $this->prepareInvoiceFormData($data))
            ->action(fn (array $data) => $this->handleCreateInvoice($data));
    }

    private function handleCreateInvoice(array $data): void
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

        $this->resetComponent();
    }

    private function getInvoiceFormDefaults(?array $invoice): array
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

        if ($lineItems === []) {
            $lineItems[] = $this->blankLineItem();
        }

        return [
            'line_items' => $lineItems,
        ];
    }

    private function fetchInvoiceLineItems(string $invoiceId): array
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

    private function resolveLineItemPrice(array $line): ?string
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
                $this->configureInvoiceFormAction(
                    Action::make('create')
                        ->icon(Heroicon::OutlinedDocumentPlus)
                        ->color('success')
                        ->outlined()
                        ->modalIcon(Heroicon::OutlinedDocumentPlus)
                        ->modalHeading('Create invoice')
                ),
                $this->configureInvoiceFormAction(
                    Action::make('duplicateLatest')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->outlined()
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
