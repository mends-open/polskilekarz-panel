<?php

namespace App\Support\Chatwoot;

use App\Services\Chatwoot\Application;
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

    public function sync(int $accountId, int $contactId, ?string $knownCustomerId = null): ?string
    {
        if ($knownCustomerId !== null && $this->identifierMatchesContact($knownCustomerId, $contactId)) {
            $this->updateContactIdentifier($accountId, $contactId, $knownCustomerId);

            return $knownCustomerId;
        }

        $identifier = $this->getCurrentIdentifier($accountId, $contactId);

        if ($identifier !== null && $this->identifierMatchesContact($identifier, $contactId)) {
            return $identifier;
        }

        $customerId = $this->findCustomerId($contactId);

        if ($customerId === null) {
            return null;
        }

        if ($identifier !== $customerId) {
            $this->updateContactIdentifier($accountId, $contactId, $customerId);
        }

        return $customerId;
    }

    public function findCustomerId(int $contactId): ?string
    {
        try {
            $response = $this->stripe->customers->search([
                'query' => \stripeSearchQuery()
                    ->metadata('chatwoot_contact_id')
                    ->equals((string) $contactId)
                    ->toString(),
                'limit' => 1,
            ])->toArray();
        } catch (ApiErrorException $exception) {
            Log::warning('Failed to search Stripe customers for Chatwoot identifier sync.', [
                'contact_id' => $contactId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        $customer = \data_get($response, 'data.0');

        if (! is_array($customer) || ($customer['deleted'] ?? false) === true) {
            return null;
        }

        $customerId = Arr::get($customer, 'id');

        return is_string($customerId) && $customerId !== '' ? $customerId : null;
    }

    private function getContact(int $accountId, int $contactId): ?array
    {
        $application = $this->applicationForAccount($accountId, $contactId);

        if ($application === null) {
            return null;
        }

        try {
            $response = $application
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

    private function getCurrentIdentifier(int $accountId, int $contactId): ?string
    {
        $contact = $this->getContact($accountId, $contactId);

        if ($contact === null) {
            return null;
        }

        return $this->normalizeIdentifier(Arr::get($contact, 'identifier'));
    }

    private function identifierMatchesContact(string $customerId, int $contactId): bool
    {
        $customer = $this->retrieveCustomer($customerId);

        if ($customer === null) {
            return false;
        }

        $metadata = Arr::get($customer, 'metadata');

        if (! is_array($metadata)) {
            return false;
        }

        if ((string) Arr::get($metadata, 'chatwoot_contact_id') !== (string) $contactId) {
            return false;
        }

        return true;
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

    private function updateContactIdentifier(int $accountId, int $contactId, string $customerId): void
    {
        $application = $this->applicationForAccount($accountId, $contactId);

        if ($application === null) {
            return;
        }

        try {
            $application
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

    private function applicationForAccount(int $accountId, int $contactId): ?Application
    {
        try {
            return $this->chatwoot->impersonateFallback($accountId);
        } catch (Throwable $exception) {
            Log::warning('Failed to impersonate Chatwoot fallback user for identifier sync.', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
