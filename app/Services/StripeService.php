<?php

namespace App\Services;

use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.api_key'));
    }

    public function createCustomer(string $name, string $email, array $metadata = [])
    {
        return $this->client->customers->create([
            'name' => $name,
            'email' => $email,
            'metadata' => $metadata,
        ]);
    }

    public function retrieveCustomer(string $customerId)
    {
        return $this->client->customers->retrieve($customerId);
    }

    public function invoices(string $customerId)
    {
        return $this->client->invoices->all(['customer' => $customerId]);
    }

    public function paymentIntents(string $customerId)
    {
        return $this->client->paymentIntents->all(['customer' => $customerId]);
    }
}

