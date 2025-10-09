<?php

namespace App\Support\Metadata;

final class Metadata
{
    public const KEY_CHATWOOT_ACCOUNT_ID = 'chatwoot_account_id';
    public const KEY_CHATWOOT_INBOX_ID = 'chatwoot_inbox_id';
    public const KEY_CHATWOOT_CONVERSATION_ID = 'chatwoot_conversation_id';
    public const KEY_CHATWOOT_CONTACT_ID = 'chatwoot_contact_id';
    public const KEY_CHATWOOT_USER_ID = 'chatwoot_user_id';
    public const KEY_USER_ID = 'user_id';
    public const KEY_STRIPE_CUSTOMER_ID = 'stripe_customer_id';
    public const KEY_STRIPE_INVOICE_ID = 'stripe_invoice_id';
    public const KEY_STRIPE_BILLING_PORTAL_SESSION = 'stripe_billing_portal_session';

    /**
     * Keys are sorted by priority order before falling back to alphabetical ordering.
     *
     * @var array<int, string>
     */
    private const PRIORITY_KEYS = [
        self::KEY_CHATWOOT_ACCOUNT_ID,
        self::KEY_CHATWOOT_INBOX_ID,
        self::KEY_CHATWOOT_CONVERSATION_ID,
        self::KEY_CHATWOOT_CONTACT_ID,
        self::KEY_CHATWOOT_USER_ID,
        self::KEY_USER_ID,
        self::KEY_STRIPE_CUSTOMER_ID,
        self::KEY_STRIPE_INVOICE_ID,
        self::KEY_STRIPE_BILLING_PORTAL_SESSION,
    ];

    /**
     * @param array<string, mixed> $items
     * @return array<string, string>
     */
    public static function prepare(array $items): array
    {
        $filtered = [];

        foreach ($items as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! is_scalar($value)) {
                continue;
            }

            $stringValue = (string) $value;

            if ($stringValue === '') {
                continue;
            }

            $filtered[$key] = $stringValue;
        }

        if ($filtered === []) {
            return [];
        }

        return self::sort($filtered);
    }

    /**
     * @param array<string, string> $base
     * @param array<string, mixed> $items
     * @return array<string, string>
     */
    public static function extend(array $base, array $items): array
    {
        if ($items === []) {
            return $base;
        }

        return self::prepare(array_replace($base, $items));
    }

    /**
     * @param array<string, string> $items
     * @return array<string, string>
     */
    private static function sort(array $items): array
    {
        $ordered = [];

        foreach (self::PRIORITY_KEYS as $key) {
            if (! array_key_exists($key, $items)) {
                continue;
            }

            $ordered[$key] = $items[$key];
            unset($items[$key]);
        }

        if ($items !== []) {
            ksort($items);
        }

        return $ordered + $items;
    }
}
