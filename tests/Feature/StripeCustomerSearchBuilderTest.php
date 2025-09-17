<?php

use App\Services\Stripe\CustomerSearchBuilder;
use App\Services\StripeService;
use Stripe\SearchResult;

beforeEach(function () {
    config(['services.stripe.api_key' => 'sk_test_dummy']);
});

afterEach(function () {
    \Mockery::close();
});

it('returns a builder when no query is provided', function () {
    $service = new StripeService();

    expect($service->searchCustomers())->toBeInstanceOf(CustomerSearchBuilder::class);
});

it('builds metadata clauses with implicit AND logic', function () {
    $service = new StripeService();

    $query = $service->searchCustomers()
        ->whereMetadata('region_eide', 'west_xrob')
        ->whereMetadata('status', 'active')
        ->toQueryString();

    expect($query)->toBe("metadata['region_eide']:'west_xrob' AND metadata['status']:'active'");
});

it('supports grouping metadata clauses with OR logic', function () {
    $service = new StripeService();

    $query = $service->searchCustomers()
        ->whereMetadata('region_eide', 'west_xrob')
        ->orWhereGroup(function (CustomerSearchBuilder $group) {
            $group->whereMetadata('plan', 'premium')
                ->orWhereMetadata('plan', 'vip');
        })
        ->toQueryString();

    expect($query)->toBe("metadata['region_eide']:'west_xrob' OR (metadata['plan']:'premium' OR metadata['plan']:'vip')");
});

it('passes the compiled query to the Stripe API when executed', function () {
    $service = new class extends StripeService {
        public ?string $capturedQuery = null;

        public function __construct()
        {
            // Avoid setting an API key when running tests.
        }

        public function searchCustomers(?string $query = null): SearchResult|CustomerSearchBuilder
        {
            if ($query === null) {
                return new CustomerSearchBuilder($this);
            }

            $this->capturedQuery = $query;

            return \Mockery::mock(SearchResult::class);
        }
    };

    $result = $service->searchCustomers()
        ->whereMetadata('region', 'east')
        ->get();

    expect($service->capturedQuery)->toBe("metadata['region']:'east'");
    expect($result)->toBeInstanceOf(SearchResult::class);
});

it('requires at least one clause before executing the search', function () {
    $service = new class extends StripeService {
        public function __construct()
        {
            // Avoid setting an API key when running tests.
        }

        public function searchCustomers(?string $query = null): SearchResult|CustomerSearchBuilder
        {
            if ($query === null) {
                return new CustomerSearchBuilder($this);
            }

            throw new \RuntimeException('The builder should prevent executing empty queries.');
        }
    };

    $service->searchCustomers()->get();
})->throws(InvalidArgumentException::class);
