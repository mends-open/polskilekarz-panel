<?php

namespace App\Services\Stripe\SearchBuilders;

use App\Enums\Stripe\Currency;
use Stripe\Exception\ApiErrorException;
use Stripe\SearchResult;

class Price extends Base
{
    public function whereCurrency(Currency $currency): static
    {
        return $this->where('currency', strtolower($currency->value));
    }

    /**
     * @throws ApiErrorException
     */
    protected function runSearch(string $query, array $options): SearchResult
    {
        return $this->service->searchPrices($query, $options);
    }
}
