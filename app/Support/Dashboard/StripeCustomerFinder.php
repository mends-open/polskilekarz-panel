<?php

namespace App\Support\Dashboard;

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

        if ($customerId === null) {
            return StripeContext::empty();
        }

        return new StripeContext($customerId);
    }
}
