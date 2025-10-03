<?php

namespace App\Support\Dashboard;

use App\Jobs\Stripe\SyncChatwootContactIdentifier;
use App\Support\Chatwoot\ContactIdentifierSynchronizer;

readonly class StripeCustomerFinder
{
    public function __construct(private ContactIdentifierSynchronizer $synchronizer) {}

    public function forChatwootContact(?int $contactId, ?int $accountId): StripeContext
    {
        if ($contactId === null || $accountId === null) {
            return StripeContext::empty();
        }

        $customerId = $this->synchronizer->sync($accountId, $contactId);

        if ($customerId !== null) {
            return new StripeContext($customerId);
        }

        $fallbackId = $this->synchronizer->findCustomerId($accountId, $contactId);

        if ($fallbackId !== null) {
            SyncChatwootContactIdentifier::dispatch($accountId, $contactId);

            return new StripeContext($fallbackId);
        }

        return StripeContext::empty();
    }
}
