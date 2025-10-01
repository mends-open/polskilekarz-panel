<?php

namespace App\Support\Dashboard;

readonly class StripeContext
{
    public function __construct(
        public ?string $customerId,
        public array $previousCustomerIds = [],
    ) {}

    public static function empty(): self
    {
        return new self(null, []);
    }

    public static function fromArray(?array $data): self
    {
        if (empty($data)) {
            return self::empty();
        }

        return new self(
            $data['customer_id'] ?? null,
            array_values($data['previous_customer_ids'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'previous_customer_ids' => $this->previousCustomerIds,
        ];
    }

    public function hasCustomer(): bool
    {
        return $this->customerId !== null;
    }
}
