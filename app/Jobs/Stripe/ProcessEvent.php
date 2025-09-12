<?php

namespace App\Jobs\Stripe;

use App\Models\StripeEvent;
use App\Services\StripeService;
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

    public function handle(StripeService $service): void
    {
        Log::info('Processing Stripe event', ['id' => $this->event->id]);

        $service->processEvent($this->event);

        Log::info('Processed Stripe event', ['id' => $this->event->id]);
    }
}
