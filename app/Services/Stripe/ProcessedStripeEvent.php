<?php

namespace App\Services\Stripe;

use Stripe\ThinEvent;
use Stripe\V2\Event;

readonly class ProcessedStripeEvent
{
    public function __construct(
        public ThinEvent $thinEvent,
        public Event $event,
    ) {
    }
}
