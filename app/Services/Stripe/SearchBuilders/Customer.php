<?php

namespace App\Services\Stripe\SearchBuilders;

use Stripe\SearchResult;

class Customer extends Base
{
    protected function runSearch(string $query, array $options): SearchResult
    {
        return $this->service->searchCustomers($query, $options);
    }
}
