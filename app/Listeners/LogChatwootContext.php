<?php

namespace App\Listeners;

use App\Events\ChatwootContextReceived;
use Illuminate\Support\Facades\Log;

class LogChatwootContext
{
    /**
     * Handle the event.
     */
    public function __invoke(ChatwootContextReceived $event): void
    {
        Log::info('Chatwoot dashboard context received.', [
            'context' => $event->context,
        ]);
    }
}
