<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\SearchResult;
use Stripe\Stripe;
use Stripe\Collection;
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
    public function createCustomer(string $name, string $email, array $metadata = []): Customer
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
    public function getCustomerById(string $id): Customer
    {
        return Customer::retrieve($id);
    }

    /**
     * @throws ApiErrorException
     */
    public function updateCustomer(string $id, ?string $name, ?string $email, ?array $metadata): Customer
    {
        return Customer::update($id, [
            $name ??'name' => $name,
            $email ?? 'email' => $email,
            $metadata ?? 'metadata' => $metadata,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function searchCustomersByMetadata(array $filterTree): SearchResult
    {
        $query = $this->buildStripeSearchQuery($filterTree);

        return Customer::search([
            'query' => $query,
        ]);
    }

    private function buildStripeSearchQuery(array $node): string
    {
        // Supported node shapes:
        // - ['and' => [ ...nodes ]]
        // - ['or'  => [ ...nodes ]]
        // - ['meta' => ['key' => 'k', 'op' => 'eq|prefix|neq|exists|not_exists', 'value' => 'v']]
        // 'eq' -> metadata['k']:'v'
        // 'neq' -> -metadata['k']:'v'
        // 'prefix' -> metadata['k']~'v*'
        // 'exists' -> has:metadata['k']
        // 'not_exists' -> -has:metadata['k']

        if (isset($node['and'])) {
            $parts = array_map(fn ($child) => $this->wrapIfNeeded($this->buildStripeSearchQuery($child)), $node['and']);
            return implode(' AND ', $parts);
        }

        if (isset($node['or'])) {
            $parts = array_map(fn ($child) => $this->wrapIfNeeded($this->buildStripeSearchQuery($child)), $node['or']);
            return implode(' OR ', $parts);
        }

        if (isset($node['meta'])) {
            $key = $node['meta']['key'] ?? null;
            $op = $node['meta']['op'] ?? 'eq';
            $value = $node['meta']['value'] ?? null;

            if ($key === null) {
                throw new \InvalidArgumentException("meta.key is required");
            }

            $field = "metadata['" . str_replace("'", "\\'", $key) . "']";

            return match ($op) {
                'eq'        => $field . ":'" . $this->escapeValue($value) . "'",
                'neq'       => '-' . $field . ":'" . $this->escapeValue($value) . "'",
                'prefix'    => $field . "~'" . $this->escapeValue($value) . "*'",
                'exists'    => "has:" . $field,
                'not_exists'=> "-has:" . $field,
                default     => throw new \InvalidArgumentException("Unsupported op: {$op}")
            };
        }

        throw new \InvalidArgumentException('Invalid filter node');
    }

    private function wrapIfNeeded(string $expr): string
    {
        // Add parentheses if the expression contains a space (i.e., compound)
        return str_contains($expr, ' ') ? "({$expr})" : $expr;
    }

    private function escapeValue(?string $value): string
    {
        $value = $value ?? '';
        // Escape single quotes per Stripe SQ syntax
        return str_replace("'", "\\'", $value);
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
