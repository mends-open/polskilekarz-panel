<?php

use App\Services\Stripe\Customers;
use App\Services\Stripe\Prices;
use App\Services\Stripe\SearchBuilders\Customer as CustomerSearchBuilder;
use App\Services\Stripe\SearchBuilders\Price as PriceSearchBuilder;
use App\Services\StripeService;
use Stripe\SearchResult;
use Stripe\Service\CustomerService;
use Stripe\Service\PriceService;
use Stripe\StripeClientInterface;

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

    expect($service->customers())->toBeInstanceOf(Customers::class);
    expect($service->prices())->toBeInstanceOf(Prices::class);
    expect($service->customers()->search())->toBeInstanceOf(CustomerSearchBuilder::class);
    expect($service->prices()->search())->toBeInstanceOf(PriceSearchBuilder::class);
});

it('builds metadata clauses with implicit AND logic', function () {
    $service = new StripeService;

    $query = $service->customers()->search()
        ->whereMetadata('region_eide', 'west_xrob')
        ->whereMetadata('status', 'active')
        ->toQueryString();

    expect($query)->toBe("metadata['region_eide']:'west_xrob' AND metadata['status']:'active'");
});

it('requires a value when filtering by metadata field', function () {
    $service = new StripeService;

    $service->customers()->search()->whereMetadata('region_eide');
})->throws(InvalidArgumentException::class);

it('supports grouping metadata clauses with OR logic', function () {
    $service = new StripeService;

    $query = $service->customers()->search()
        ->whereMetadata('region_eide', 'west_xrob')
        ->orWhereMetadata(function (CustomerSearchBuilder $group) {
            $group->whereMetadata('plan', 'premium')
                ->orWhereMetadata('plan', 'vip');
        })
        ->toQueryString();

    expect($query)->toBe("metadata['region_eide']:'west_xrob' OR (metadata['plan']:'premium' OR metadata['plan']:'vip')");
});

it('passes the compiled customer query to the Stripe API when executed', function () {
    $customerService = new class (\Mockery::mock(StripeClientInterface::class)) extends CustomerService
    {
        public ?array $capturedParams = null;

        public mixed $capturedOptions = null;

        public function search($params = null, $opts = null)
        {
            $this->capturedParams = $params;
            $this->capturedOptions = $opts;

            return \Mockery::mock(SearchResult::class);
        }
    };

    $service = new StripeService(customerService: $customerService);

    $result = $service->customers()->search()
        ->whereMetadata('region', 'east')
        ->expand('data.invoice')
        ->limit(15)
        ->get();

    expect($customerService->capturedParams)->toBe([
        'query' => "metadata['region']:'east'",
        'expand' => ['data.invoice'],
        'limit' => 15,
    ]);
    expect($customerService->capturedOptions)->toBeNull();
    expect($result)->toBeInstanceOf(SearchResult::class);
});

it('requires at least one clause before executing a customer search', function () {
    $customerService = new class (\Mockery::mock(StripeClientInterface::class)) extends CustomerService
    {
        public function search($params = null, $opts = null)
        {
            throw new RuntimeException('The builder should prevent executing empty queries.');
        }
    };

    $service = new StripeService(customerService: $customerService);

    $service->customers()->search()->get();
})->throws(InvalidArgumentException::class);

it('builds price clauses for currency filters', function () {
    $service = new StripeService;

    $query = $service->prices()->search()
        ->whereCurrency(DummyCurrency::EUR)
        ->toQueryString();

    expect($query)->toBe("currency:'eur'");
});

it('passes the compiled price query to the Stripe API when executed', function () {
    $priceService = new class (\Mockery::mock(StripeClientInterface::class)) extends PriceService
    {
        public ?array $capturedParams = null;

        public mixed $capturedOptions = null;

        public function search($params = null, $opts = null)
        {
            $this->capturedParams = $params;
            $this->capturedOptions = $opts;

            return \Mockery::mock(SearchResult::class);
        }
    };

    $service = new StripeService(priceService: $priceService);

    $service->prices()->search()
        ->where('active', 'true')
        ->orWhere(function (PriceSearchBuilder $group) {
            $group->where('nickname', 'standard')->orWhere('nickname', 'premium');
        })
        ->page('cursor_123')
        ->expand(['data.product'])
        ->get();

    expect($priceService->capturedParams)->toBe([
        'query' => "active:'true' OR (nickname:'standard' OR nickname:'premium')",
        'expand' => ['data.product'],
        'page' => 'cursor_123',
    ]);
    expect($priceService->capturedOptions)->toBeNull();
});

it('validates the configured limit and page options', function () {
    $service = new StripeService;

    $builder = $service->customers()->search();

    expect(fn () => $builder->limit(0))->toThrow(InvalidArgumentException::class);
    expect(fn () => $builder->limit(101))->toThrow(InvalidArgumentException::class);
    expect(fn () => $builder->page(''))->toThrow(InvalidArgumentException::class);
});
