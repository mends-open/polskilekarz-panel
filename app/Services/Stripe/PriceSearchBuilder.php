<?php

namespace App\Services\Stripe;

use BackedEnum;
use Stringable;
use Stripe\SearchResult;

class PriceSearchBuilder extends AbstractSearchBuilder
{
    public function whereCurrency(string|BackedEnum|Stringable $currency): static
    {
        if ($currency instanceof BackedEnum) {
            $code = $currency->value;
        } else {
            $code = (string) $currency;
        }

        return $this->where('currency', strtolower($code));
    }

    protected function runSearch(string $query): SearchResult
    {
        return $this->service->searchPrices($query);
    }
}
