<?php

namespace App\Http\Controllers;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('services.stripe.api_key'));

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

        $event = StripeEvent::create(['data' => $stripeEvent->toArray()]);

        Log::info('Stored Stripe event', ['id' => $event->id]);

        ProcessEvent::dispatch($event);

        Log::info('Dispatched Stripe event for processing', ['id' => $event->id]);

        return response()->json(['id' => $event->id]);
    }
}
