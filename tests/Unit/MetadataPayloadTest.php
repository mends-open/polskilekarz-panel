<?php

declare(strict_types=1);

use App\Support\Metadata\MetadataPayload;

it('orders metadata deterministically with priority keys first', function () {
    $payload = MetadataPayload::from([
        'random_key' => 'random',
        MetadataPayload::KEY_USER_ID => 10,
        MetadataPayload::KEY_CHATWOOT_ACCOUNT_ID => '1',
        MetadataPayload::KEY_STRIPE_INVOICE_ID => 'in_123',
        MetadataPayload::KEY_CHATWOOT_CONTACT_ID => '5',
        MetadataPayload::KEY_CHATWOOT_CONVERSATION_ID => '3',
        MetadataPayload::KEY_CHATWOOT_USER_ID => '7',
        MetadataPayload::KEY_CHATWOOT_INBOX_ID => '2',
    ])->with([
        MetadataPayload::KEY_STRIPE_CUSTOMER_ID => 'cus_456',
        'zeta' => 'last',
        'alpha' => 'first',
    ]);

    expect($payload->toArray())->toBe([
        MetadataPayload::KEY_CHATWOOT_ACCOUNT_ID => '1',
        MetadataPayload::KEY_CHATWOOT_INBOX_ID => '2',
        MetadataPayload::KEY_CHATWOOT_CONVERSATION_ID => '3',
        MetadataPayload::KEY_CHATWOOT_CONTACT_ID => '5',
        MetadataPayload::KEY_CHATWOOT_USER_ID => '7',
        MetadataPayload::KEY_USER_ID => '10',
        MetadataPayload::KEY_STRIPE_CUSTOMER_ID => 'cus_456',
        MetadataPayload::KEY_STRIPE_INVOICE_ID => 'in_123',
        'alpha' => 'first',
        'random_key' => 'random',
        'zeta' => 'last',
    ]);
});

it('drops empty or non-string keys and values', function () {
    $payload = MetadataPayload::from([
        '' => 'ignored',
        15 => 'ignored',
        MetadataPayload::KEY_CHATWOOT_ACCOUNT_ID => null,
        MetadataPayload::KEY_CHATWOOT_INBOX_ID => '',
        MetadataPayload::KEY_CHATWOOT_CONVERSATION_ID => 0,
        'custom' => true,
    ]);

    expect($payload->toArray())->toBe([
        MetadataPayload::KEY_CHATWOOT_CONVERSATION_ID => '0',
        'custom' => '1',
    ]);
});
