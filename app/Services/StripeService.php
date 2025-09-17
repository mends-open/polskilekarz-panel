<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Illuminate\Support\Str;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\SearchResult;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.api_key'));
    }

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(string $name, string $email, ?array $metadata): Customer
    {
        return Customer::create([
            'name' => $name,
            'email' => $email,
            $metadata ?? 'metadata' => $metadata,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function getCustomer(string $customerId): Customer
    {
        return Customer::retrieve($customerId);
    }

    /**
     * @throws ApiErrorException
     */
    public function updateCustomer(string $customerId, ?string $name, ?string $email, ?array $metadata): Customer
    {
        return Customer::update($customerId, [
            $name ?? 'name' => $name,
            $email ?? 'email' => $email,
            $metadata ?? 'metadata' => $metadata,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function searchCustomers(array $query): SearchResult
    {
        return Customer::search($query);
    }

    public function buildQueryClause(string $field, string $operator, string $value): string
    {
        return $field . $operator . '"' . Str::of($value)->replace('"', '\\"') . '"';
    }

    /**
     * Construct a Stripe event from the given payload and signature.
     *
     * @param string $payload
     * @param string $signature
     * @return Event
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
