<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use InvalidArgumentException;
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
    public function searchCustomersByMetadata(array $filters): SearchResult
    {
        $query = $this->buildStripeSearchQuery($filters);

        return Customer::search([
            'query' => $query,
        ]);
    }

    private function buildStripeSearchQuery(array $filters): string
    {
        if ($filters === []) {
            throw new InvalidArgumentException('Filter array cannot be empty');
        }

        if ($this->isAssociative($filters)) {
            return $this->buildAndExpression($filters);
        }

        $groups = array_map(function ($group, $index) {
            if (!is_array($group) || $group === [] || !$this->isAssociative($group)) {
                throw new InvalidArgumentException(sprintf('Each OR group must be a non-empty associative array (index %d).', $index));
            }

            return $this->wrapIfNeeded($this->buildAndExpression($group));
        }, $filters, array_keys($filters));

        return implode(' OR ', $groups);
    }

    private function buildAndExpression(array $conditions): string
    {
        $parts = [];

        foreach ($conditions as $metadataKey => $details) {
            if (!is_string($metadataKey) || $metadataKey === '') {
                throw new InvalidArgumentException('Metadata key must be a non-empty string.');
            }

            $parts[] = $this->buildMetadataCondition($metadataKey, $details);
        }

        if ($parts === []) {
            throw new InvalidArgumentException('Each AND group must contain at least one condition.');
        }

        return implode(' AND ', $parts);
    }

    private function buildMetadataCondition(string $key, mixed $details): string
    {
        $operator = 'eq';
        $value = $details;

        if (is_array($details) && $this->isAssociative($details)) {
            $operator = $details['operator'] ?? $details['op'] ?? 'eq';
            $value = $details['value'] ?? null;
        }

        $field = "metadata['" . str_replace("'", "\\'", $key) . "']";

        return match ($operator) {
            'eq' => $field . ":'" . $this->escapeValue($this->formatValue($value)) . "'",
            'neq' => '-' . $field . ":'" . $this->escapeValue($this->formatValue($value)) . "'",
            'prefix' => $field . "~'" . $this->escapeValue($this->formatValue($value)) . "*'",
            'exists' => 'has:' . $field,
            'not_exists' => '-has:' . $field,
            default => throw new InvalidArgumentException(sprintf('Unsupported operator "%s".', $operator)),
        };
    }

    private function wrapIfNeeded(string $expr): string
    {
        // Add parentheses if the expression contains a space (i.e., compound)
        return str_contains($expr, ' ') ? "({$expr})" : $expr;
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value) || is_string($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        throw new InvalidArgumentException('Metadata value must be a scalar or null.');
    }

    private function isAssociative(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) !== range(0, count($array) - 1);
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
