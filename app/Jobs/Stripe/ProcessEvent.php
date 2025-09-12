<?php

namespace App\Jobs\Stripe;

use App\Models\StripeEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public StripeEvent $event)
    {
    }

    public function handle(): void
    {
        Log::info('Processing Stripe event', ['id' => $this->event->id]);

        $metadata = data_get($this->event->data, 'data.object.metadata', []);

        $context = [
            'chatwoot_account_id' => data_get($metadata, 'chatwoot_account_id'),
            'chatwoot_conversation_id' => data_get($metadata, 'chatwoot_conversation_id'),
            'chatwoot_contact_id' => data_get($metadata, 'chatwoot_contact_id'),
            'chatwoot_user_id' => data_get($metadata, 'chatwoot_user_id'),
            'chatwoot_message_id' => data_get($metadata, 'chatwoot_message_id') ?? data_get($metadata, 'chatwoot_last_message_id'),
        ];

        $context = array_filter($context, fn ($value) => ! is_null($value));

        if ($context) {
            $this->event->chatwootContexts()->create($context);
            Log::info('Stored Chatwoot context for Stripe event', ['event_id' => $this->event->id]);
        }

        Log::info('Processed Stripe event', ['id' => $this->event->id]);
    }
}
