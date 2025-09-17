<?php

namespace App\Services\Stripe\SearchBuilders;

use App\Enums\Stripe\Currency;
use BackedEnum;

class Price extends Base
{
    public function whereCurrency(Currency|BackedEnum|string $currency): static
    {
        if ($currency instanceof BackedEnum) {
            $currency = $currency->value;
        }

        return $this->where('currency', strtolower((string) $currency));
    }
}
