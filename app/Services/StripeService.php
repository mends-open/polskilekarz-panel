<?php

namespace App\Services;

use App\Exceptions\StripeVisibilityException;
use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use InvalidArgumentException;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\Price;
use Stripe\SearchResult;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Webhook;

/**
 * Service layer for interacting with Stripe API.
 */
class StripeService
{
    private StripeClient $client;

    private string $visibleSinceKey;

    public function __construct(?StripeClient $client = null)
    {
        $this->client = $client ?? new StripeClient(config('services.stripe.api_key'));
        $this->visibleSinceKey = (string) config('services.stripe.visible_since_key', '');
    }

    /**
     * Build request options for the Stripe API.
     */
    private function options(?string $stripeAccount = null): array
    {
        return $stripeAccount ? ['stripe_account' => $stripeAccount] : [];
    }

    /**
     * Optionally attach expand parameters.
     */
    private function addExpand(array $params, array $expand): array
    {
        if ($expand !== []) {
            $params['expand'] = $expand;
        }

        return $params;
    }

    /**
     * Attach the visibility metadata when requested.
     */
    private function withVisibilityMetadata(array $metadata, bool $markVisible): array
    {
        if (! $markVisible || $this->visibleSinceKey === '') {
            return $metadata;
        }

        return $metadata + [$this->visibleSinceKey => now()->getTimestamp()];
    }

    /**
     * Ensure the given Stripe resource passes the visibility check.
     */
    private function ensureVisible(object $resource, bool $requireVisible, string $resourceName): void
    {
        if (! $requireVisible) {
            return;
        }

        if (! $this->isVisible($resource)) {
            throw new StripeVisibilityException("Stripe {$resourceName} is not visible yet.");
        }
    }

    /**
     * Determine if a Stripe resource should be visible.
     */
    private function isVisible(object $resource): bool
    {
        if ($this->visibleSinceKey === '') {
            return true;
        }

        if (! isset($resource->metadata)) {
            return true;
        }

        $metadata = $resource->metadata;

        if ($metadata instanceof StripeObject) {
            $metadata = $metadata->toArray();
        } elseif (! is_array($metadata)) {
            $metadata = [];
        }

        $value = $metadata[$this->visibleSinceKey] ?? null;

        if ($value === null || $value === '') {
            return true;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
        } else {
            $timestamp = strtotime((string) $value) ?: null;
        }

        if ($timestamp === null) {
            return true;
        }

        return $timestamp <= now()->getTimestamp();
    }

    /**
     * Filter a Stripe list response by visibility when requested.
     *
     * @template T of \Stripe\StripeObject
     * @param  (Collection<T>|SearchResult<T>)  $collection
     * @return Collection<T>|SearchResult<T>
     */
    private function filterVisibleList(Collection|SearchResult $collection, bool $visibleOnly): Collection|SearchResult
    {
        if (! $visibleOnly || ! isset($collection->data) || ! is_array($collection->data)) {
            return $collection;
        }

        $collection->data = array_values(array_filter(
            $collection->data,
            fn ($item) => $this->isVisible($item)
        ));

        if (property_exists($collection, 'total_count')) {
            $collection->total_count = count($collection->data);
        }

        return $collection;
    }

    /**
     * Normalise the invoice items into price/quantity pairs.
     *
     * @return array<int, array{price: string, quantity: int}>
     */
    private function normaliseInvoiceItems(array $items): array
    {
        $normalised = [];

        foreach ($items as $key => $value) {
            $price = null;
            $quantity = 1;

            if (is_int($key)) {
                if (is_array($value)) {
                    $price = $value['price'] ?? null;
                    $quantity = $value['quantity'] ?? $quantity;
                } else {
                    $price = $value;
                }
            } else {
                $price = $key;
                $quantity = $value;
            }

            if (is_array($quantity)) {
                $quantity = $quantity['quantity'] ?? 1;
            }

            if (! is_string($price) || $price === '') {
                throw new InvalidArgumentException('Each invoice item must include a price ID.');
            }

            if (! is_numeric($quantity)) {
                throw new InvalidArgumentException("Quantity for price {$price} must be numeric.");
            }

            $quantity = (int) $quantity;

            if ($quantity < 1) {
                throw new InvalidArgumentException("Quantity for price {$price} must be at least 1.");
            }

            $normalised[] = [
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        if ($normalised === []) {
            throw new InvalidArgumentException('At least one invoice item is required.');
        }

        return $normalised;
    }

    /**
     * Build the metadata search query for grouped conditions.
     */
    private function buildMetadataQuery(array $metadataGroups): string
    {
        $orSegments = [];

        foreach ($metadataGroups as $group) {
            if (! is_array($group) || $group === []) {
                continue;
            }

            $andSegments = [];

            foreach ($group as $key => $value) {
                $escapedKey = addcslashes((string) $key, "'\\");
                $escapedValue = addcslashes((string) $value, "'\\");
                $andSegments[] = "metadata['{$escapedKey}']:'{$escapedValue}'";
            }

            if ($andSegments !== []) {
                $orSegments[] = '('.implode(' AND ', $andSegments).')';
            }
        }

        if ($orSegments === []) {
            throw new InvalidArgumentException('At least one metadata condition must be provided.');
        }

        return implode(' OR ', $orSegments);
    }

    /**
     * Create a Stripe customer.
     */
    public function createCustomer(
        string $name,
        string $email,
        array $metadata = [],
        array $expand = [],
        bool $markVisible = false,
        ?string $stripeAccount = null,
    ): Customer {
        $params = $this->addExpand([
            'name' => $name,
            'email' => $email,
            'metadata' => $this->withVisibilityMetadata($metadata, $markVisible),
        ], $expand);

        return $this->client->customers->create($params, $this->options($stripeAccount));
    }

    /**
     * Retrieve a customer by id.
     */
    public function getCustomerById(
        string $customerId,
        array $expand = [],
        bool $requireVisible = false,
        ?string $stripeAccount = null,
    ): Customer {
        $customer = $this->client->customers->retrieve(
            $customerId,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );

        $this->ensureVisible($customer, $requireVisible, 'customer');

        return $customer;
    }

    /**
     * Search customers by grouped metadata conditions.
     *
     * @param  array<array<string, string|int>>  $metadataGroups  Each group is ANDed, groups are ORed.
     */
    public function getCustomersByMetadata(
        array $metadataGroups,
        array $expand = [],
        bool $visibleOnly = false,
        ?string $stripeAccount = null,
    ): SearchResult {
        $query = $this->buildMetadataQuery($metadataGroups);
        $params = $this->addExpand(['query' => $query], $expand);

        $result = $this->client->customers->search($params, $this->options($stripeAccount));

        return $this->filterVisibleList($result, $visibleOnly);
    }

    /**
     * Locate customers created from Chatwoot metadata.
     */
    public function getCustomersForChatwoot(
        int $accountId,
        array $contactIds = [],
        array $conversationIds = [],
        array $expand = [],
        bool $visibleOnly = false,
        ?string $stripeAccount = null,
    ): SearchResult {
        $groups = [];

        if ($contactIds === [] && $conversationIds === []) {
            $groups[] = ['chatwoot_account_id' => $accountId];
        }

        foreach ($contactIds as $contactId) {
            $groups[] = [
                'chatwoot_account_id' => $accountId,
                'chatwoot_contact_id' => $contactId,
            ];
        }

        foreach ($conversationIds as $conversationId) {
            $groups[] = [
                'chatwoot_account_id' => $accountId,
                'chatwoot_conversation_id' => $conversationId,
            ];
        }

        return $this->getCustomersByMetadata($groups, $expand, $visibleOnly, $stripeAccount);
    }

    /**
     * Retrieve a price by id.
     */
    public function getPriceById(
        string $priceId,
        array $expand = [],
        bool $requireVisible = false,
        ?string $stripeAccount = null,
    ): Price {
        $price = $this->client->prices->retrieve(
            $priceId,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );

        $this->ensureVisible($price, $requireVisible, 'price');

        return $price;
    }

    /**
     * List prices for a currency.
     */
    public function getPricesByCurrency(
        string $currency,
        bool $active = true,
        array $expand = [],
        bool $visibleOnly = false,
        ?string $stripeAccount = null,
    ): Collection {
        $params = $this->addExpand([
            'currency' => $currency,
            'active' => $active,
        ], $expand);

        $prices = $this->client->prices->all($params, $this->options($stripeAccount));

        return $this->filterVisibleList($prices, $visibleOnly);
    }

    /**
     * List prices for a given product.
     */
    public function getPricesForProduct(
        string $productId,
        bool $active = true,
        array $expand = [],
        bool $visibleOnly = false,
        ?string $stripeAccount = null,
    ): Collection {
        $params = $this->addExpand([
            'product' => $productId,
            'active' => $active,
        ], $expand);

        $prices = $this->client->prices->all($params, $this->options($stripeAccount));

        return $this->filterVisibleList($prices, $visibleOnly);
    }

    /**
     * Create and finalize an invoice for a customer.
     *
     * The $items array may be:
     * - A simple list of price IDs (quantity defaults to 1)
     * - An associative array of priceId => quantity
     * - A list of arrays with explicit price and optional quantity keys
     */
    public function createInvoice(
        string $customerId,
        array $items,
        array $invoiceParams = [],
        array $expand = [],
        bool $requireVisible = false,
        ?string $stripeAccount = null,
    ): Invoice {
        $normalisedItems = $this->normaliseInvoiceItems($items);

        $invoicePayload = $this->addExpand(array_merge([
            'customer' => $customerId,
            'auto_advance' => false,
        ], $invoiceParams), $expand);

        $invoice = $this->client->invoices->create($invoicePayload, $this->options($stripeAccount));

        foreach ($normalisedItems as $item) {
            $this->client->invoiceItems->create([
                'customer' => $customerId,
                'invoice' => $invoice->id,
                'price' => $item['price'],
                'quantity' => $item['quantity'],
            ], $this->options($stripeAccount));
        }

        $finalized = $this->client->invoices->finalizeInvoice(
            $invoice->id,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );

        $this->ensureVisible($finalized, $requireVisible, 'invoice');

        return $finalized;
    }

    /**
     * Retrieve an invoice by id.
     */
    public function getInvoiceById(
        string $invoiceId,
        array $expand = [],
        bool $requireVisible = false,
        ?string $stripeAccount = null,
    ): Invoice {
        $invoice = $this->client->invoices->retrieve(
            $invoiceId,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );

        $this->ensureVisible($invoice, $requireVisible, 'invoice');

        return $invoice;
    }

    /**
     * Get payments (charges) associated with an invoice.
     *
     * @return array<int, \Stripe\Charge>
     */
    public function getPaymentsForInvoice(
        string $invoiceId,
        array $expand = [],
        bool $visibleOnly = false,
        ?string $stripeAccount = null,
    ): array {
        $invoice = $this->client->invoices->retrieve(
            $invoiceId,
            ['expand' => array_merge(['payment_intent.charges'], $expand)],
            $this->options($stripeAccount)
        );

        $this->ensureVisible($invoice, $visibleOnly, 'invoice');

        $charges = $invoice->payment_intent->charges->data ?? [];

        if ($visibleOnly) {
            $charges = array_values(array_filter(
                $charges,
                fn ($charge) => $this->isVisible($charge)
            ));
        }

        return $charges;
    }

    /**
     * Construct a Stripe event from the given payload and signature.
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public function constructEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );
    }

    /**
     * Store the given Stripe event in the database.
     */
    public function storeEvent(Event $stripeEvent): StripeEvent
    {
        return StripeEvent::create(['data' => $stripeEvent->toArray()]);
    }

    /**
     * Dispatch the given Stripe event for further processing.
     */
    public function dispatchEvent(StripeEvent $event): void
    {
        ProcessEvent::dispatch($event);
    }
}
