<?php

namespace App\Jobs\Chatwoot;

use App\Models\ChatwootEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ChatwootEvent $event)
    {
    }

    public function handle(): void
    {
        Log::info('Processing Chatwoot event', ['id' => $this->event->id]);

        Log::info('Processed Chatwoot event', ['id' => $this->event->id]);
    }
}
