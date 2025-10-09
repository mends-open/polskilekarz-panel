<?php

declare(strict_types=1);

use App\Support\Metadata\Metadata;

it('orders metadata deterministically with priority keys first', function () {
    $payload = Metadata::extend(Metadata::prepare([
        'random_key' => 'random',
        Metadata::KEY_USER_ID => 10,
        Metadata::KEY_CHATWOOT_ACCOUNT_ID => '1',
        Metadata::KEY_STRIPE_INVOICE_ID => 'in_123',
        Metadata::KEY_CHATWOOT_CONTACT_ID => '5',
        Metadata::KEY_CHATWOOT_CONVERSATION_ID => '3',
        Metadata::KEY_CHATWOOT_USER_ID => '7',
        Metadata::KEY_CHATWOOT_INBOX_ID => '2',
    ]), [
        Metadata::KEY_STRIPE_CUSTOMER_ID => 'cus_456',
        'zeta' => 'last',
        'alpha' => 'first',
    ]);

    expect($payload)->toBe([
        Metadata::KEY_CHATWOOT_ACCOUNT_ID => '1',
        Metadata::KEY_CHATWOOT_INBOX_ID => '2',
        Metadata::KEY_CHATWOOT_CONVERSATION_ID => '3',
        Metadata::KEY_CHATWOOT_CONTACT_ID => '5',
        Metadata::KEY_CHATWOOT_USER_ID => '7',
        Metadata::KEY_USER_ID => '10',
        Metadata::KEY_STRIPE_CUSTOMER_ID => 'cus_456',
        Metadata::KEY_STRIPE_INVOICE_ID => 'in_123',
        'alpha' => 'first',
        'random_key' => 'random',
        'zeta' => 'last',
    ]);
});

it('drops empty or non-string keys and values', function () {
    $payload = Metadata::prepare([
        '' => 'ignored',
        15 => 'ignored',
        Metadata::KEY_CHATWOOT_ACCOUNT_ID => null,
        Metadata::KEY_CHATWOOT_INBOX_ID => '',
        Metadata::KEY_CHATWOOT_CONVERSATION_ID => 0,
        'custom' => true,
    ]);

    expect($payload)->toBe([
        Metadata::KEY_CHATWOOT_CONVERSATION_ID => '0',
        'custom' => '1',
    ]);
});
