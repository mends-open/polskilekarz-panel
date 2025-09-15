<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\Price;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Service layer for interacting with Stripe API.
 */
class StripeService
{
    private StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.api_key'));
    }

    /**
     * Build options for a request.
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
     * Create a Stripe customer.
     */
    public function createCustomer(
        string $name,
        string $email,
        array $metadata = [],
        array $expand = [],
        ?string $stripeAccount = null,
    ): Customer {
        $params = $this->addExpand([
            'name' => $name,
            'email' => $email,
            'metadata' => $metadata,
        ], $expand);

        return $this->client->customers->create($params, $this->options($stripeAccount));
    }

    /**
     * Retrieve a customer by id.
     */
    public function getCustomerById(string $customerId, array $expand = [], ?string $stripeAccount = null): Customer
    {
        return $this->client->customers->retrieve(
            $customerId,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );
    }

    /**
     * Search customers by Chatwoot related metadata.
     *
     * @param string $accountId Chatwoot account identifier.
     * @param array $contactIds Optional contact identifiers.
     * @param array $conversationIds Optional conversation identifiers.
     */
    public function getCustomersByMetadata(
        string $accountId,
        array $contactIds = [],
        array $conversationIds = [],
        array $expand = [],
        ?string $stripeAccount = null,
    ) {
        $query = "metadata['chatwoot_account_id']:'{$accountId}'";

        $filters = [];
        foreach ($contactIds as $id) {
            $filters[] = "metadata['chatwoot_contact_id']:'{$id}'";
        }
        foreach ($conversationIds as $id) {
            $filters[] = "metadata['chatwoot_conversation_id']:'{$id}'";
        }

        if ($filters !== []) {
            $query .= ' AND (' . implode(' OR ', $filters) . ')';
        }

        $params = $this->addExpand(['query' => $query], $expand);

        return $this->client->customers->search($params, $this->options($stripeAccount));
    }

    /**
     * Retrieve a price by id.
     */
    public function getPriceById(string $priceId, array $expand = [], ?string $stripeAccount = null): Price
    {
        return $this->client->prices->retrieve(
            $priceId,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );
    }

    /**
     * List prices for a currency.
     */
    public function getPricesByCurrency(
        string $currency,
        bool $active = true,
        array $expand = [],
        ?string $stripeAccount = null,
    ) {
        $params = $this->addExpand([
            'currency' => $currency,
            'active' => $active,
        ], $expand);

        return $this->client->prices->all($params, $this->options($stripeAccount));
    }

    /**
     * List prices for a given product.
     */
    public function getPricesForProduct(
        string $productId,
        bool $active = true,
        array $expand = [],
        ?string $stripeAccount = null,
    ) {
        $params = $this->addExpand([
            'product' => $productId,
            'active' => $active,
        ], $expand);

        return $this->client->prices->all($params, $this->options($stripeAccount));
    }

    /**
     * Create an invoice for the given customer and prices.
     *
     * @param array<string, int> $priceQuantities Map of price IDs to quantities.
     */
    public function createInvoice(
        string $customerId,
        array $priceQuantities,
        array $expand = [],
        ?string $stripeAccount = null,
    ): Invoice {
        foreach ($priceQuantities as $priceId => $quantity) {
            $this->client->invoiceItems->create([
                'customer' => $customerId,
                'price' => $priceId,
                'quantity' => $quantity,
            ], $this->options($stripeAccount));
        }

        $invoice = $this->client->invoices->create([
            'customer' => $customerId,
            'auto_advance' => false,
        ], $this->options($stripeAccount));

        return $this->client->invoices->finalizeInvoice(
            $invoice->id,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );
    }

    /**
     * Retrieve an invoice by id.
     */
    public function getInvoiceById(string $invoiceId, array $expand = [], ?string $stripeAccount = null): Invoice
    {
        return $this->client->invoices->retrieve(
            $invoiceId,
            $this->addExpand([], $expand),
            $this->options($stripeAccount)
        );
    }

    /**
     * Get payments (charges) associated with an invoice.
     */
    public function getPaymentsForInvoice(
        string $invoiceId,
        array $expand = [],
        ?string $stripeAccount = null,
    ): array {
        $invoice = $this->client->invoices->retrieve(
            $invoiceId,
            ['expand' => array_merge(['payment_intent.charges'], $expand)],
            $this->options($stripeAccount)
        );

        return $invoice->payment_intent->charges->data ?? [];
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

