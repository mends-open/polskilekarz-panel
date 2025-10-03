<?php

namespace App\Support\Chatwoot;

use App\Services\Chatwoot\ChatwootClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class ContactIdentifierSynchronizer
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly ChatwootClient $chatwoot,
    ) {}

    public function sync(int $accountId, int $contactId): ?string
    {
        $contact = $this->getContact($accountId, $contactId);

        if ($contact === null) {
            return null;
        }

        $identifier = $this->normalizeIdentifier(Arr::get($contact, 'identifier'));

        if ($identifier !== null && $this->customerIsValid($identifier, $accountId, $contactId)) {
            return $identifier;
        }

        $customer = $this->findLatestCustomer($accountId, $contactId);

        if ($customer === null) {
            return null;
        }

        $customerId = (string) Arr::get($customer, 'id');

        if ($identifier !== $customerId) {
            $this->updateContactIdentifier($accountId, $contactId, $customerId);
        }

        return $customerId;
    }

    private function getContact(int $accountId, int $contactId): ?array
    {
        try {
            $response = $this->chatwoot
                ->application()
                ->contacts()
                ->get($accountId, $contactId);
        } catch (Throwable $exception) {
            Log::warning('Failed to fetch Chatwoot contact for identifier sync.', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        $payload = \data_get($response, 'payload');

        return is_array($payload) ? $payload : null;
    }

    private function normalizeIdentifier(mixed $identifier): ?string
    {
        if (! is_string($identifier)) {
            return null;
        }

        $identifier = trim($identifier);

        return $identifier === '' ? null : $identifier;
    }

    private function customerIsValid(string $customerId, int $accountId, int $contactId): bool
    {
        $customer = $this->retrieveCustomer($customerId);

        if ($customer === null) {
            return false;
        }

        if ($this->customerMatches($customer, $accountId, $contactId)) {
            return true;
        }

        return $this->customerMatches($customer, $accountId, $contactId, requireAccount: false);
    }

    private function retrieveCustomer(string $customerId): ?array
    {
        try {
            $customer = $this->stripe->customers->retrieve($customerId, []);
        } catch (ApiErrorException $exception) {
            Log::info('Unable to retrieve Stripe customer for identifier sync.', [
                'customer_id' => $customerId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        $payload = $customer->toArray();

        if (($payload['deleted'] ?? false) === true) {
            return null;
        }

        return $payload;
    }

    private function findLatestCustomer(int $accountId, int $contactId): ?array
    {
        $queries = [
            [
                \stripeSearchQuery()
                    ->metadata('chatwoot_account_id')
                    ->equals((string) $accountId)
                    ->andMetadata('chatwoot_contact_id')
                    ->equals((string) $contactId)
                    ->toString(),
                true,
                'account_and_contact',
            ],
            [
                \stripeSearchQuery()
                    ->metadata('chatwoot_contact_id')
                    ->equals((string) $contactId)
                    ->toString(),
                false,
                'contact_only',
            ],
        ];

        foreach ($queries as [$query, $requireAccount, $mode]) {
            $customer = $this->searchLatestCustomer($query, $accountId, $contactId, $mode);

            if ($customer === null) {
                continue;
            }

            if ($this->customerMatches($customer, $accountId, $contactId, $requireAccount)) {
                return $customer;
            }
        }

        return null;
    }

    private function searchLatestCustomer(string $query, int $accountId, int $contactId, string $mode): ?array
    {
        try {
            $response = $this->stripe->customers->search([
                'query' => $query,
                'limit' => 1,
            ])->toArray();
        } catch (ApiErrorException $exception) {
            Log::warning('Failed to search Stripe customers for identifier sync.', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'mode' => $mode,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        $customer = \data_get($response, 'data.0');

        if (! is_array($customer)) {
            return null;
        }

        if (($customer['deleted'] ?? false) === true) {
            return null;
        }

        return $customer;
    }

    private function customerMatches(array $customer, int $accountId, int $contactId, bool $requireAccount = true): bool
    {
        $metadata = Arr::get($customer, 'metadata');

        if (! is_array($metadata)) {
            return false;
        }

        $contactMatches = (string) Arr::get($metadata, 'chatwoot_contact_id') === (string) $contactId;

        if (! $contactMatches) {
            return false;
        }

        $accountValue = Arr::get($metadata, 'chatwoot_account_id');

        if ($requireAccount) {
            return (string) $accountValue === (string) $accountId;
        }

        if ($accountValue === null) {
            return true;
        }

        return (string) $accountValue === (string) $accountId;
    }

    private function updateContactIdentifier(int $accountId, int $contactId, string $customerId): void
    {
        try {
            $this->chatwoot
                ->application()
                ->contacts()
                ->update($accountId, $contactId, [
                    'identifier' => $customerId,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to update Chatwoot contact identifier.', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'customer_id' => $customerId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
