<?php

namespace App\Services\Stripe;

use RuntimeException;
use Stripe\Service\V2\Core\CoreServiceFactory;
use Stripe\Service\V2\Core\EventService;
use Stripe\Service\V2\V2ServiceFactory;
use Stripe\StripeClient;

class ThinEventProcessor
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly ?string $webhookSecret = null,
    ) {
    }

    public function handle(string $payload, string $signature): ProcessedStripeEvent
    {
        $secret = $this->webhookSecret ?? (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        $thinEvent = $this->stripe->parseThinEvent($payload, $signature, $secret);

        $options = [];

        if (!empty($thinEvent->context)) {
            $options['stripe_account'] = $thinEvent->context;
        }

        /** @var V2ServiceFactory $v2 */
        $v2 = $this->stripe->getService('v2');
        /** @var CoreServiceFactory $core */
        $core = $v2->getService('core');
        /** @var EventService $events */
        $events = $core->getService('events');

        $event = $events->retrieve(
            $thinEvent->id,
            null,
            empty($options) ? null : $options
        );

        return new ProcessedStripeEvent($thinEvent, $event);
    }
}
