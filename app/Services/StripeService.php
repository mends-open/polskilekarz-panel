<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use App\Services\Stripe\Customers;
use App\Services\Stripe\Prices;
use App\Services\Stripe\Search\QueryFormatter;
use App\Services\Stripe\Search\SearchParametersBuilder;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Service\CustomerService;
use Stripe\Service\PriceService;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    private readonly StripeClient $client;

    private readonly QueryFormatter $queryFormatter;

    private readonly SearchParametersBuilder $parametersBuilder;

    private ?CustomerService $customerService;

    private ?PriceService $priceService;

    private ?Customers $customersGateway = null;

    private ?Prices $pricesGateway = null;

    public function __construct(
        ?StripeClient $client = null,
        ?CustomerService $customerService = null,
        ?PriceService $priceService = null,
        ?QueryFormatter $queryFormatter = null,
        ?SearchParametersBuilder $parametersBuilder = null,
    ) {
        $this->client = $client ?? $this->createStripeClient();
        $this->customerService = $customerService;
        $this->priceService = $priceService;
        $this->queryFormatter = $queryFormatter ?? new QueryFormatter();
        $this->parametersBuilder = $parametersBuilder ?? new SearchParametersBuilder();
    }

    public function client(): StripeClient
    {
        return $this->client;
    }

    public function customers(): Customers
    {
        if ($this->customersGateway === null) {
            $service = $this->customerService ?? $this->client->customers;

            $this->customersGateway = new Customers(
                $service,
                $this->queryFormatter,
                $this->parametersBuilder,
            );
        }

        return $this->customersGateway;
    }

    public function prices(): Prices
    {
        if ($this->pricesGateway === null) {
            $service = $this->priceService ?? $this->client->prices;

            $this->pricesGateway = new Prices(
                $service,
                $this->queryFormatter,
                $this->parametersBuilder,
            );
        }

        return $this->pricesGateway;
    }

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(string $name, string $email, ?array $metadata): Customer
    {
        return $this->customers()->create([
            'name' => $name,
            'email' => $email,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function getCustomer(string $customerId): Customer
    {
        return $this->customers()->retrieve($customerId);
    }

    /**
     * @throws ApiErrorException
     */
    public function updateCustomer(string $customerId, ?string $name, ?string $email, ?array $metadata): Customer
    {
        return $this->customers()->update($customerId, array_filter([
            'name' => $name,
            'email' => $email,
            'metadata' => $metadata,
        ], static fn ($value) => $value !== null));
    }

    /**
     * @throws SignatureVerificationException
     */
    public function constructEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );
    }

    public function storeEvent(Event $stripeEvent): StripeEvent
    {
        return StripeEvent::create(['data' => $stripeEvent->toArray()]);
    }

    public function dispatchEvent(StripeEvent $event): void
    {
        ProcessEvent::dispatch($event);
    }

    protected function createStripeClient(): StripeClient
    {
        return new StripeClient([
            'api_key' => config('services.stripe.api_key'),
        ]);
    }
}
