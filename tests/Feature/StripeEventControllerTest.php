<?php

use App\Http\Controllers\StripeEventController;
use App\Services\Stripe\ProcessedStripeEvent;
use App\Services\Stripe\ThinEventProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\ThinEvent;
use Stripe\V2\Event;

it('processes stripe thin events', function () {
    $payload = ['id' => 'evt_123'];
    $signature = 'sig_header';

    $request = Request::create(
        '/events/stripe',
        'POST',
        server: ['HTTP_Stripe-Signature' => $signature],
        content: json_encode($payload)
    );

    $thinEvent = new ThinEvent();
    $thinEvent->id = 'evt_123';
    $thinEvent->type = 'customer.created';

    $event = Event::constructFrom([
        'id' => 'evt_123',
        'object' => 'v2.core.event',
        'type' => 'customer.created',
        'created' => now()->getTimestamp(),
    ], null, 'v2');

    $processedEvent = new ProcessedStripeEvent($thinEvent, $event);

    $processor = \Mockery::mock(ThinEventProcessor::class);
    $processor->shouldReceive('handle')
        ->once()
        ->with(json_encode($payload), $signature)
        ->andReturn($processedEvent);

    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')->never();
    $logger->shouldReceive('error')->never();
    $logger->shouldReceive('info')
        ->once()
        ->with('Processed Stripe event', \Mockery::on(function (array $context) {
            return $context['id'] === 'evt_123'
                && $context['type'] === 'customer.created'
                && $context['context'] === null;
        }));

    $controller = new StripeEventController($logger);

    $response = $controller($request, $processor);

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true))->toBe([
        'id' => 'evt_123',
        'type' => 'customer.created',
    ]);
});

it('rejects requests with missing signatures', function () {
    $request = Request::create('/events/stripe', 'POST');

    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')->once()->with('Stripe signature header missing');
    $logger->shouldReceive('error')->never();
    $logger->shouldReceive('info')->never();

    $controller = new StripeEventController($logger);

    $response = $controller($request, \Mockery::mock(ThinEventProcessor::class));

    expect($response->getStatusCode())->toBe(400);
    expect($response->getData(true))->toBe(['error' => 'Missing signature']);
});

it('rejects requests with invalid signatures', function () {
    $payload = ['id' => 'evt_123'];
    $signature = 'sig_header';

    $request = Request::create(
        '/events/stripe',
        'POST',
        server: ['HTTP_Stripe-Signature' => $signature],
        content: json_encode($payload)
    );

    $processor = \Mockery::mock(ThinEventProcessor::class);
    $processor->shouldReceive('handle')
        ->once()
        ->with(json_encode($payload), $signature)
        ->andThrow(new SignatureVerificationException('Invalid signature'));

    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')
        ->once()
        ->with('Stripe signature verification failed', \Mockery::on(fn (array $context) => $context['exception'] === 'Invalid signature'));
    $logger->shouldReceive('error')->never();
    $logger->shouldReceive('info')->never();

    $controller = new StripeEventController($logger);

    $response = $controller($request, $processor);

    expect($response->getStatusCode())->toBe(400);
    expect($response->getData(true))->toBe(['error' => 'Invalid signature']);
});
