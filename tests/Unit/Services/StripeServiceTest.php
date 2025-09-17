<?php

use App\Services\StripeService;
use Faker\Factory as FakerFactory;

describe('StripeService metadata search query builder', function () {
    beforeEach(function () {
        $this->faker = FakerFactory::create();
        $this->invokeBuildQuery = function (array $filters) {
            $reflection = new \ReflectionClass(StripeService::class);
            $service = $reflection->newInstanceWithoutConstructor();
            $method = $reflection->getMethod('buildStripeSearchQuery');
            $method->setAccessible(true);

            return $method->invoke($service, $filters);
        };
    });

    it('builds an AND query from associative metadata filters', function () {
        $firstKey = $this->faker->lexify('key_????');
        $secondKey = $this->faker->lexify('key_????');
        $firstValue = (string) $this->faker->randomDigitNotNull();
        $secondValue = (string) $this->faker->randomDigitNotNull();

        $query = ($this->invokeBuildQuery)([
            $firstKey => $firstValue,
            $secondKey => $secondValue,
        ]);

        expect($query)->toBe(
            "metadata['{$firstKey}']:'{$firstValue}' AND metadata['{$secondKey}']:'{$secondValue}'"
        );
    });

    it('builds grouped OR queries from a list of associative filters', function () {
        $firstKey = $this->faker->lexify('first_????');
        $secondKey = $this->faker->lexify('second_????');
        $thirdKey = $this->faker->lexify('third_????');
        $firstValue = $this->faker->lexify('val_????');
        $secondValue = $this->faker->lexify('val_????');
        $thirdValue = $this->faker->lexify('val_????');

        $query = ($this->invokeBuildQuery)([
            [
                $firstKey => $firstValue,
                $secondKey => $secondValue,
            ],
            [
                $thirdKey => $thirdValue,
            ],
        ]);

        expect($query)->toBe(
            "(metadata['{$firstKey}']:'{$firstValue}' AND metadata['{$secondKey}']:'{$secondValue}') OR metadata['{$thirdKey}']:'{$thirdValue}'"
        );
    });

    it('supports advanced operators for metadata filters', function () {
        $visibleKey = $this->faker->lexify('visible_????');
        $activeKey = $this->faker->lexify('active_????');

        $query = ($this->invokeBuildQuery)([
            $visibleKey => ['operator' => 'exists'],
            $activeKey => ['operator' => 'neq', 'value' => false],
        ]);

        expect($query)->toBe(
            "has:metadata['{$visibleKey}'] AND -metadata['{$activeKey}']:'false'"
        );
    });

    it('rejects empty filter collections', function () {
        expect(fn () => ($this->invokeBuildQuery)([]))
            ->toThrow(new \InvalidArgumentException('Filter array cannot be empty'));
    });

    it('rejects invalid OR group shapes', function () {
        expect(fn () => ($this->invokeBuildQuery)([
            [$this->faker->word(), $this->faker->word()],
        ]))->toThrow(\InvalidArgumentException::class);
    });
});
