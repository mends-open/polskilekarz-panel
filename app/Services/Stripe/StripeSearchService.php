<?php

namespace App\Services\Stripe;

class StripeSearchService
{
    public function __construct(private readonly StripeService $service)
    {
    }

    public function customers(): CustomerSearchBuilder
    {
        return new CustomerSearchBuilder($this->service);
    }

    public function prices(): PriceSearchBuilder
    {
        return new PriceSearchBuilder($this->service);
    }
}
