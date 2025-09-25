<?php

namespace App\Listeners;

use App\Events\ChatwootContextReceived;
use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Events\MessageReceived;

class LogChatwootContext
{
    public function handle(MessageReceived $event): void
    {
        $message = json_decode($event->message, true);

        if (! is_array($message)) {
            return;
        }

        if (($message['event'] ?? null) !== 'client-context') {
            return;
        }

        if (($message['channel'] ?? null) !== 'private-chatwoot.context') {
            return;
        }

        $data = $message['data'] ?? [];

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        if (! is_array($data)) {
            return;
        }

        Log::info('Received Chatwoot context', [
            'payload' => $data,
        ]);

        broadcast(new ChatwootContextReceived($data));
    }
}
