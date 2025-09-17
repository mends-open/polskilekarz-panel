<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use App\Services\Stripe\Search;
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

    public function search(): Search
    {
        return new Search($this);
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
    public function searchCustomers(string $query, array $options = []): SearchResult
    {
        return Customer::search($this->compileSearchParameters($query, $options));
    }

    /**
     * @throws ApiErrorException
     */
    public function searchPrices(string $query, array $options = []): SearchResult
    {
        return Price::search($this->compileSearchParameters($query, $options));
    }

    public function buildQueryClause(string $field, string $value, string $operator = ':'): string
    {
        $field = Str::of($field)->trim()->toString();
        $op = Str::of($operator)->trim()->lower()->toString();
        $op = $op === '' ? ':' : $op;

        if ($op === 'has' || $op === 'has:') {
            return 'has:'.$field;
        }

        $normalized = Str::of($value)->trim();

        if ($normalized->isEmpty()) {
            $formatted = "''";
        } elseif (is_numeric($normalized->toString())) {
            $formatted = $normalized->toString();
        } else {
            $escaped = Str::of($normalized->toString())
                ->replace('\\', '\\\\')
                ->replace("'", "\\'")
                ->toString();
            $formatted = "'".$escaped."'";
        }

        return $field.$op.$formatted;
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

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function compileSearchParameters(string $query, array $options): array
    {
        $payload = ['query' => $query];

        if (isset($options['expand']) && $options['expand'] !== []) {
            $payload['expand'] = array_values(array_map('strval', $options['expand']));
        }

        if (isset($options['limit'])) {
            $payload['limit'] = (int) $options['limit'];
        }

        if (isset($options['page'])) {
            $payload['page'] = (string) $options['page'];
        }

        return $payload;
    }
}
