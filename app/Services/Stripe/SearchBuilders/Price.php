<?php

namespace App\Services\Stripe\SearchBuilders;

use App\Enums\Stripe\Currency;
use BackedEnum;
use Stripe\Exception\ApiErrorException;
use Stripe\SearchResult;

class Price extends Base
{
    public function whereCurrency(Currency|BackedEnum|string $currency): static
    {
        if ($currency instanceof BackedEnum) {
            $currency = $currency->value;
        }

        return $this->where('currency', strtolower((string) $currency));
    }

    /**
     * @throws ApiErrorException
     */
    protected function runSearch(string $query, array $options): SearchResult
    {
        return $this->service->searchPrices($query, $options);
    }
}
