<?php

namespace App\Http\Controllers;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeEventController extends Controller
{
    public function __invoke(Request $request, StripeClient $stripe): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $stripeEvent = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $exception) {
            Log::warning('Stripe signature verification failed', ['exception' => $exception->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $stripeEvent = $stripe->events->retrieve(
                $stripeEvent->id,
                [],
                $this->stripeOptions($stripeEvent)
            );
        } catch (ApiErrorException $exception) {
            Log::error('Failed to retrieve Stripe event payload', [
                'event_id' => $stripeEvent->id ?? null,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(
                ['error' => 'Unable to retrieve event payload'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $event = StripeEvent::create(['data' => $stripeEvent->toArray()]);

        Log::info('Stored Stripe event', ['id' => $event->id]);

        ProcessEvent::dispatch($event);

        Log::info('Dispatched Stripe event for processing', ['id' => $event->id]);

        return response()->json(['id' => $event->id]);
    }

    private function stripeOptions(object $stripeEvent): array
    {
        if (!empty($stripeEvent->account)) {
            return ['stripe_account' => $stripeEvent->account];
        }

        return [];
    }
}
