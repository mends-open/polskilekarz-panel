<?php

namespace App\Services\Stripe;

use App\Services\Stripe\Search\QueryFormatter;
use App\Services\Stripe\Search\SearchParametersBuilder;
use App\Services\Stripe\SearchBuilders\Customer as CustomerSearchBuilder;
use Stripe\Service\CustomerService;

class Customers
{
    public function __construct(
        private readonly CustomerService $service,
        private readonly QueryFormatter $formatter,
        private readonly SearchParametersBuilder $parameters,
    ) {
    }

    public function search(): CustomerSearchBuilder
    {
        return new CustomerSearchBuilder(
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
