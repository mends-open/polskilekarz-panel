<?php

namespace App\Services;

use App\Models\StripeEvent;

class StripeService
{
    public function storeEvent(array $payload): StripeEvent
    {
        $event = StripeEvent::create([
            'data' => $payload,
        ]);

        $metadata = data_get($payload, 'data.object.metadata', []);

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
        }

        return $event;
    }
}
