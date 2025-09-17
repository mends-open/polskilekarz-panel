<?php

namespace Tests\Unit\Services;

use App\Services\StripeService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class StripeServiceTest extends TestCase
{
    private function invokeBuildQuery(array $filters): string
    {
        $reflection = new ReflectionClass(StripeService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('buildStripeSearchQuery');
        $method->setAccessible(true);

        return $method->invoke($service, $filters);
    }

    public function testBuildsAndQueryFromAssociativeArray(): void
    {
        $query = $this->invokeBuildQuery([
            'foo' => 'bar',
            'baz' => 2,
        ]);

        $this->assertSame(
            "metadata['foo']:'bar' AND metadata['baz']:'2'",
            $query
        );
    }

    public function testBuildsOrGroupsFromList(): void
    {
        $query = $this->invokeBuildQuery([
            ['foo' => 'bar', 'baz' => 'qux'],
            ['alpha' => 'beta'],
        ]);

        $this->assertSame(
            "(metadata['foo']:'bar' AND metadata['baz']:'qux') OR metadata['alpha']:'beta'",
            $query
        );
    }

    public function testSupportsAdvancedOperators(): void
    {
        $query = $this->invokeBuildQuery([
            'visible' => ['operator' => 'exists'],
            'active' => ['operator' => 'neq', 'value' => false],
        ]);

        $this->assertSame(
            "has:metadata['visible'] AND -metadata['active']:'false'",
            $query
        );
    }

    public function testRejectsEmptyFilters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter array cannot be empty');

        $this->invokeBuildQuery([]);
    }

    public function testRejectsInvalidOrGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Each OR group must be a non-empty associative array');

        $this->invokeBuildQuery([
            ['foo', 'bar'],
        ]);
    }
}
