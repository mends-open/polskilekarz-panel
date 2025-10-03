<?php

namespace App\Support\Dashboard;

use App\Support\Chatwoot\ContactIdentifierSynchronizer;

readonly class StripeCustomerFinder
{
    public function __construct(private ContactIdentifierSynchronizer $synchronizer) {}

    public function findFallback(?int $accountId, ?int $contactId): ?string
    {
        if ($accountId === null || $contactId === null) {
            return null;
        }

        return $this->synchronizer->findCustomerId($accountId, $contactId);
    }
}
