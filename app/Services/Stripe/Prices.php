<?php

namespace App\Services\Stripe;

use App\Services\Stripe\Search\QueryFormatter;
use App\Services\Stripe\Search\SearchParametersBuilder;
use App\Services\Stripe\SearchBuilders\Price as PriceSearchBuilder;
use Stripe\Service\PriceService;

class Prices
{
    public function __construct(
        private readonly PriceService $service,
        private readonly QueryFormatter $formatter,
        private readonly SearchParametersBuilder $parameters,
    ) {
    }

    public function search(): PriceSearchBuilder
    {
        return new PriceSearchBuilder(
            fn (string $query, array $options) => $this->service->search(
                $this->parameters->build($query, $options),
            ),
            $this->formatter,
        );
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->service->{$method}(...$parameters);
    }
}
