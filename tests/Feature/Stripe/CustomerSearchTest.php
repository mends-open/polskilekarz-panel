<?php

use App\Services\StripeService;
use Faker\Factory as FakerFactory;
use Stripe\Customer as StripeCustomer;
use Stripe\SearchResult;

describe('Stripe customer metadata search', function () {
    beforeEach(function () {
        $this->faker = FakerFactory::create();

        $apiKey = config('services.stripe.api_key') ?? env('STRIPE_API_KEY');

        if (empty($apiKey)) {
            $this->markTestSkipped('Stripe API key not configured.');
        }

        config()->set('services.stripe.api_key', $apiKey);
        $this->service = app(StripeService::class);
    });

    it('sends metadata queries to the Stripe Search API without errors', function () {
        $metadataKey = 'test_meta_' . $this->faker->lexify('????');
        $metadataValue = $this->faker->uuid();

        $customer = $this->service->createCustomer(
            $this->faker->name(),
            $this->faker->unique()->safeEmail(),
            [
                $metadataKey => $metadataValue,
            ]
        );

        try {
            $result = $this->service->searchCustomersByMetadata([
                $metadataKey => $metadataValue,
            ]);

            expect($result)
                ->toBeInstanceOf(SearchResult::class)
                ->and($result->object)->toBe('search_result')
                ->and($result->url)->toBe('/v1/customers/search');

            expect(collect($result->data)->every(fn ($item) => $item instanceof StripeCustomer))->toBeTrue();
        } finally {
            StripeCustomer::update($customer->id, ['metadata' => []]);
            $customer->delete();
        }
    });
});
