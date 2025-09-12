<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;

class StripeEventController extends Controller
{
    public function __construct(private StripeService $stripeService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $stripeEvent = $this->stripeService->constructEvent($payload, $signature);
        } catch (SignatureVerificationException $exception) {
            Log::warning('Stripe signature verification failed', ['exception' => $exception->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->stripeService->storeEvent($stripeEvent);

        Log::info('Stored Stripe event', ['id' => $event->id]);

        $this->stripeService->dispatchEvent($event);

        Log::info('Dispatched Stripe event for processing', ['id' => $event->id]);

        return response()->json(['id' => $event->id]);
    }
}
