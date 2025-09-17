<?php

use App\Services\Stripe\Search;
use App\Services\Stripe\SearchBuilders\Customer;
use App\Services\Stripe\SearchBuilders\Price;
use App\Services\StripeService;
use Stripe\SearchResult;

enum DummyCurrency: string
{
    case EUR = 'EUR';
}

beforeEach(function () {
    config(['services.stripe.api_key' => 'sk_test_dummy']);
});

afterEach(function () {
    \Mockery::close();
});

it('provides a search gateway for Stripe resources', function () {
    $service = new StripeService;
    $search = $service->search();

    expect($search)->toBeInstanceOf(Search::class);
    expect($search->customers())->toBeInstanceOf(Customer::class);
    expect($search->prices())->toBeInstanceOf(Price::class);
});

it('builds metadata clauses with implicit AND logic', function () {
    $service = new StripeService;

    $query = $service->search()->customers()
        ->whereMetadata('region_eide', 'west_xrob')
        ->whereMetadata('status', 'active')
        ->toQueryString();

    expect($query)->toBe("metadata['region_eide']:'west_xrob' AND metadata['status']:'active'");
});

it('requires a value when filtering by metadata field', function () {
    $service = new StripeService;

    $service->search()->customers()->whereMetadata('region_eide');
})->throws(InvalidArgumentException::class);

it('supports grouping metadata clauses with OR logic', function () {
    $service = new StripeService;

    $query = $service->search()->customers()
        ->whereMetadata('region_eide', 'west_xrob')
        ->orWhereMetadata(function (Customer $group) {
            $group->whereMetadata('plan', 'premium')
                ->orWhereMetadata('plan', 'vip');
        })
        ->toQueryString();

    expect($query)->toBe("metadata['region_eide']:'west_xrob' OR (metadata['plan']:'premium' OR metadata['plan']:'vip')");
});

it('passes the compiled customer query to the Stripe API when executed', function () {
    $service = new class extends StripeService
    {
        public ?string $capturedCustomerQuery = null;

        /** @var array<string, mixed>|null */
        public ?array $capturedCustomerOptions = null;

        public function __construct()
        {
            // Avoid setting an API key when running tests.
        }

        public function searchCustomers(string $query, array $options = []): SearchResult
        {
            $this->capturedCustomerQuery = $query;
            $this->capturedCustomerOptions = $options;

            return \Mockery::mock(SearchResult::class);
        }
    };

    $result = $service->search()->customers()
        ->whereMetadata('region', 'east')
        ->extend('data.invoice')
        ->limit(15)
        ->get();

    expect($service->capturedCustomerQuery)->toBe("metadata['region']:'east'");
    expect($service->capturedCustomerOptions)->toBe([
        'expand' => ['data.invoice'],
        'limit' => 15,
    ]);
    expect($result)->toBeInstanceOf(SearchResult::class);
});

it('requires at least one clause before executing a customer search', function () {
    $service = new class extends StripeService
    {
        public function __construct()
        {
            // Avoid setting an API key when running tests.
        }

        public function searchCustomers(string $query, array $options = []): SearchResult
        {
            throw new RuntimeException('The builder should prevent executing empty queries.');
        }
    };

    $service->search()->customers()->get();
})->throws(InvalidArgumentException::class);

it('builds price clauses for currency filters', function () {
    $service = new StripeService;

    $query = $service->search()->prices()
        ->whereCurrency(DummyCurrency::EUR)
        ->toQueryString();

    expect($query)->toBe("currency:'eur'");
});

it('passes the compiled price query to the Stripe API when executed', function () {
    $service = new class extends StripeService
    {
        public ?string $capturedPriceQuery = null;

        /** @var array<string, mixed>|null */
        public ?array $capturedPriceOptions = null;

        public function __construct()
        {
            // Avoid setting an API key when running tests.
        }

        public function searchPrices(string $query, array $options = []): SearchResult
        {
            $this->capturedPriceQuery = $query;
            $this->capturedPriceOptions = $options;

            return \Mockery::mock(SearchResult::class);
        }
    };

    $service->search()->prices()
        ->where('active', 'true')
        ->orWhere(function (Price $group) {
            $group->where('nickname', 'standard')->orWhere('nickname', 'premium');
        })
        ->page('cursor_123')
        ->expand(['data.product'])
        ->get();

    expect($service->capturedPriceQuery)->toBe("active:'true' OR (nickname:'standard' OR nickname:'premium')");
    expect($service->capturedPriceOptions)->toBe([
        'page' => 'cursor_123',
        'expand' => ['data.product'],
    ]);
});

it('validates the configured limit and page options', function () {
    $service = new StripeService;

    $builder = $service->search()->customers();

    expect(fn () => $builder->limit(0))->toThrow(InvalidArgumentException::class);
    expect(fn () => $builder->limit(101))->toThrow(InvalidArgumentException::class);
    expect(fn () => $builder->page(''))->toThrow(InvalidArgumentException::class);
});
