<?php

namespace App\Services\Stripe;

use App\Services\Stripe\SearchBuilders\Customer;
use App\Services\Stripe\SearchBuilders\Price;
use App\Services\StripeService;

readonly class Search
{
    public function __construct(private StripeService $service) {}

    public function customers(): Customer
    {
        return new Customer($this->service);
    }

    public function prices(): Price
    {
        return new Price($this->service);
    }
}
