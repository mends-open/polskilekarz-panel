<?php

declare(strict_types=1);

use App\Models\ChatwootEvent;

test('chatwoot payloads retain their structure when assigned to the model', function (): void {
    $payload = [
        'event' => 'message_created',
        'id' => '1',
        'content' => 'Hi',
        'created_at' => '2020-03-03 13:05:57 UTC',
        'message_type' => 'incoming',
        'content_type' => 'enum',
        'content_attributes' => [],
        'source_id' => '',
        'sender' => [
            'id' => '1',
            'name' => 'Agent',
            'email' => 'agent@example.com',
        ],
        'contact' => [
            'id' => '1',
            'name' => 'contact-name',
        ],
        'conversation' => [
            'display_id' => '1',
            'additional_attributes' => [
                'browser' => [
                    'device_name' => 'Macbook',
                    'browser_name' => 'Chrome',
                    'platform_name' => 'Macintosh',
                    'browser_version' => '80.0.3987.122',
                    'platform_version' => '10.15.2',
                ],
                'referer' => 'http://www.chatwoot.com',
                'initiated_at' => 'Tue Mar 03 2020 18:37:38 GMT-0700 (Mountain Standard Time)',
            ],
        ],
        'account' => [
            'id' => '1',
            'name' => 'Chatwoot',
        ],
    ];

    $event = new ChatwootEvent(['data' => $payload]);

    expect($event->data)->toBe($payload);
});
