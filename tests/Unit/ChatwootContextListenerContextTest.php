<?php

declare(strict_types=1);

use App\Livewire\ChatwootContextListener;

it('extracts the conversation context from a valid payload', function (): void {
    $listener = new ChatwootContextListener();

    $extract = \Closure::bind(fn (array $payload) => $this->extractContext($payload), $listener, $listener);

    $context = $extract([
        'conversation' => [
            'id' => 1382,
            'account_id' => 1,
            'inbox_id' => 58,
            'messages' => [
                ['id' => 195736],
                ['id' => 195737],
            ],
            'meta' => [
                'sender' => [
                    'id' => 1395,
                    'type' => 'contact',
                ],
            ],
        ],
        'contact' => ['id' => 1395],
        'currentAgent' => ['id' => 5],
    ]);

    expect($context)->toMatchArray([
        'account_id' => 1,
        'conversation_id' => 1382,
        'inbox_id' => 58,
        'sender_id' => 1395,
        'sender_type' => 'contact',
        'last_message_id' => 195737,
        'contact_id' => 1395,
        'user_id' => 5,
    ]);
});

it('returns null when conversation identifiers are missing', function (): void {
    $listener = new ChatwootContextListener();

    $extract = \Closure::bind(fn (?array $payload) => $this->extractContext($payload), $listener, $listener);

    expect($extract(['conversation' => ['id' => null]]))->toBeNull();
    expect($extract(null))->toBeNull();
});
