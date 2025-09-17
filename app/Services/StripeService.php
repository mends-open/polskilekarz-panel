<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use App\Services\Stripe\CustomerSearchBuilder;
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
     * Retrieve Stripe customers matching the provided search criteria.
     *
     * When no query string is supplied a fluent query builder is returned so the
     * consumer can incrementally assemble the search clauses before executing it.
     *
     * @throws ApiErrorException
     */
    public function searchCustomers(?string $query = null): SearchResult|CustomerSearchBuilder
    {
        if ($query === null) {
            return new CustomerSearchBuilder($this);
        }

        return Customer::search([
            'query' => $query
        ]);
    }

    public function buildQueryClause(string $field, string $value, string $operator = ':'): string
    {
        $field = Str::of($field)->trim()->toString();
        $op = Str::of($operator)->trim()->lower()->toString();
        $op = $op === '' ? ':' : $op;

        // Unary "has:" operator (value ignored)
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
                // Escape backslashes and single quotes; wrap in single quotes
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
