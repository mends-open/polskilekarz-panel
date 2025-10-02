<?php

namespace App\Http\Controllers;

use App\Services\Stripe\ProcessedStripeEvent;
use App\Services\Stripe\ThinEventProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Symfony\Component\HttpFoundation\Response;

class StripeEventController extends Controller
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(Request $request, ThinEventProcessor $processor): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if ($signature === null) {
            $this->logger->warning('Stripe signature header missing');

            return new JsonResponse(['error' => 'Missing signature'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $processedEvent = $processor->handle($payload, $signature);
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            $this->logger->warning('Stripe signature verification failed', ['exception' => $exception->getMessage()]);

            return new JsonResponse(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        } catch (ApiErrorException|RuntimeException $exception) {
            $this->logger->error('Failed to retrieve Stripe event', ['exception' => $exception->getMessage()]);

            return new JsonResponse(['error' => 'Unable to retrieve event'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logProcessedEvent($processedEvent);

        return new JsonResponse([
            'id' => $processedEvent->event->id,
            'type' => $processedEvent->event->type,
        ]);
    }

    private function logProcessedEvent(ProcessedStripeEvent $processedEvent): void
    {
        $relatedObject = $processedEvent->thinEvent->related_object;

        $this->logger->info('Processed Stripe event', [
            'id' => $processedEvent->event->id,
            'type' => $processedEvent->event->type,
            'context' => $processedEvent->thinEvent->context,
            'related_object_id' => $relatedObject?->id,
            'related_object_type' => $relatedObject?->type,
        ]);
    }
}
