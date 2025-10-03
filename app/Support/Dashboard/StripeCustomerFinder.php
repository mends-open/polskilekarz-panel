<?php

namespace App\Support\Dashboard;

use App\Support\Chatwoot\ContactIdentifierSynchronizer;

readonly class StripeCustomerFinder
{
    public function __construct(private ContactIdentifierSynchronizer $synchronizer) {}

    public function findFallback(?int $contactId): ?string
    {
        if ($contactId === null) {
            return null;
        }

        return $this->synchronizer->findCustomerId($contactId);
    }
}
