<?php

namespace App\Services\Stripe;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Illuminate\Support\Str;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Price;
use Stripe\SearchResult;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.api_key'));
    }

    public function search(): StripeSearchService
    {
        return new StripeSearchService($this);
    }

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(string $name, string $email, ?array $metadata): Customer
    {
        return Customer::create([
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
        return Customer::retrieve($customerId);
    }

    /**
     * @throws ApiErrorException
     */
    public function updateCustomer(string $customerId, ?string $name, ?string $email, ?array $metadata): Customer
    {
        return Customer::update($customerId, array_filter([
            'name' => $name,
            'email' => $email,
            'metadata' => $metadata,
        ], static fn ($value) => $value !== null));
    }

    /**
     * @throws ApiErrorException
     */
    public function searchCustomers(string $query): SearchResult
    {
        return Customer::search([
            'query' => $query,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function searchPrices(string $query): SearchResult
    {
        return Price::search([
            'query' => $query,
        ]);
    }

    public function buildQueryClause(string $field, string $value, string $operator = ':'): string
    {
        $field = Str::of($field)->trim()->toString();
        $op = Str::of($operator)->trim()->lower()->toString();
        $op = $op === '' ? ':' : $op;

        if ($op === 'has' || $op === 'has:') {
            return 'has:' . $field;
        }

        $normalized = Str::of($value)->trim();

        if ($normalized->isEmpty()) {
            $formatted = "''";
        } else {
            $lower = $normalized->lower()->toString();
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                $formatted = 'true';
            } elseif (in_array($lower, ['false', '0', 'no', 'off'], true)) {
                $formatted = 'false';
            } elseif (is_numeric($normalized->toString())) {
                $formatted = $normalized->toString();
            } else {
                $escaped = Str::of($normalized->toString())
                    ->replace('\\', '\\\\')
                    ->replace("'", "\\'")
                    ->toString();
                $formatted = "'" . $escaped . "'";
            }
        }

        return $field . $op . $formatted;
    }

    public function metadataField(string $field): string
    {
        $key = Str::of($field)->trim();
        $escaped = $key->replace('\\', '\\\\')->replace("'", "\\'")->toString();

        return "metadata['{$escaped}']";
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
}
