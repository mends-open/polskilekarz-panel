<?php

namespace App\Services;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function storeEvent(array $payload): StripeEvent
    {
        $event = StripeEvent::create([
            'data' => $payload,
        ]);

        Log::info('Stored Stripe event', ['id' => $event->id]);

        ProcessEvent::dispatch($event);

        Log::info('Dispatched Stripe event for processing', ['id' => $event->id]);

        return $event;
    }

    public function processEvent(StripeEvent $event): void
    {
        $metadata = data_get($event->data, 'data.object.metadata', []);

        $context = [
            'chatwoot_account_id' => data_get($metadata, 'chatwoot_account_id'),
            'chatwoot_conversation_id' => data_get($metadata, 'chatwoot_conversation_id'),
            'chatwoot_contact_id' => data_get($metadata, 'chatwoot_contact_id'),
            'chatwoot_user_id' => data_get($metadata, 'chatwoot_user_id'),
            'chatwoot_message_id' => data_get($metadata, 'chatwoot_message_id') ?? data_get($metadata, 'chatwoot_last_message_id'),
        ];

        $context = array_filter($context, fn ($value) => ! is_null($value));

        if ($context) {
            $event->chatwootContexts()->create($context);
            Log::info('Stored Chatwoot context for Stripe event', ['event_id' => $event->id]);
        }
    }
}
