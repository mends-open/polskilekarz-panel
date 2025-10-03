<?php

namespace App\Support\Dashboard;

readonly class StripeContext
{
    public function __construct(
        public ?string $customerId,
    ) {}

    public static function empty(): self
    {
        return new self(null);
    }

    public static function fromArray(?array $data): self
    {
        if (empty($data)) {
            return self::empty();
        }

        return new self(
            $data['customer_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
        ];
    }

    public function hasCustomer(): bool
    {
        return \filled($this->customerId);
    }
}
