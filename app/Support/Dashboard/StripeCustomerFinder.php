<?php

namespace App\Support\Dashboard;

use Stripe\StripeClient;

class StripeCustomerFinder
{
    public function __construct(private readonly StripeClient $stripe)
    {
    }

    public function forChatwootContact(?int $contactId): StripeContext
    {
        if ($contactId === null) {
            return StripeContext::empty();
        }

        $query = stripeSearchQuery()
            ->metadata('chatwoot_contact_id')
            ->equals((string) $contactId);

        $response = $this->stripe->customers->search([
            'query' => $query->toString(),
        ])->toArray();

        $customers = $response['data'] ?? [];

        $primary = $customers[0]['id'] ?? null;
        $previous = collect($customers)
            ->pluck('id')
            ->skip(1)
            ->values()
            ->all();

        return new StripeContext($primary, $previous);
    }
}
