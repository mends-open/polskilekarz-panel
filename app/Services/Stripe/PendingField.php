<?php

namespace App\Services\Stripe;

use BadMethodCallException;

final class PendingField
{
    public function __construct(
        private readonly StripeSearchQuery     $query,
        private readonly string                $field,
        private readonly ?string               $boolean,
        private readonly StripeSearchFieldType $type,
        private bool                           $resolved = false,
    )
    {
    }

    public function equals(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, ':', $value, $this->type));
    }

    public function greaterThan(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '>', $value, $this->type));
    }

    public function greaterThanOrEquals(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '>=', $value, $this->type));
    }

    public function lessThan(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '<', $value, $this->type));
    }

    public function lessThanOrEquals(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '<=', $value, $this->type));
    }

    public function exists(): StripeSearchQuery
    {
        return $this->complete($this->query->buildExistence($this->field));
    }

    public function __toString(): string
    {
        throw new BadMethodCallException('Pending field must be resolved with a condition before being cast to a string.');
    }

    private function complete(string $clause): StripeSearchQuery
    {
        if (!$this->resolved) {
            $this->query->appendClause($clause, $this->boolean);
            $this->resolved = true;
        }

        return $this->query;
    }
}
