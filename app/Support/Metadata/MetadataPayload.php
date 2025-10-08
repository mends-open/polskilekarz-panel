<?php

namespace App\Support\Metadata;

use Illuminate\Contracts\Arrayable;
use JsonSerializable;

final class MetadataPayload implements Arrayable, JsonSerializable
{
    /**
     * @var array<string, string>
     */
    private array $items;

    /**
     * Keys are sorted by priority order first before falling back to alphabetical ordering.
     *
     * @var array<int, string>
     */
    private const PRIORITY_KEYS = [
        'chatwoot_account_id',
        'chatwoot_inbox_id',
        'chatwoot_conversation_id',
        'chatwoot_contact_id',
        'chatwoot_user_id',
        'user_id',
        'stripe_customer_id',
        'stripe_invoice_id',
        'billing_portal_session',
    ];

    /**
     * @param array<string, mixed> $items
     */
    private function __construct(array $items)
    {
        $this->items = $this->normalise($items);
    }

    /**
     * @param array<string, mixed> $items
     */
    public static function from(array $items): self
    {
        return new self($items);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function with(array $items): self
    {
        return new self(array_replace($this->items, $items));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, string>
     */
    private function normalise(array $items): array
    {
        $normalised = [];

        foreach ($items as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (is_scalar($value)) {
                $stringValue = (string) $value;

                if ($stringValue === '') {
                    continue;
                }

                $normalised[$key] = $stringValue;
            }
        }

        if ($normalised === []) {
            return [];
        }

        return $this->sorted($normalised);
    }

    /**
     * @param array<string, string> $items
     * @return array<string, string>
     */
    private function sorted(array $items): array
    {
        $ordered = [];

        foreach (self::PRIORITY_KEYS as $key) {
            if (array_key_exists($key, $items)) {
                $ordered[$key] = $items[$key];
                unset($items[$key]);
            }
        }

        if ($items !== []) {
            ksort($items);
        }

        return $ordered + $items;
    }
}
