<?php

use App\Services\Stripe\ThinEventProcessor;
use Stripe\Service\V2\Core\CoreServiceFactory;
use Stripe\Service\V2\Core\EventService;
use Stripe\Service\V2\V2ServiceFactory;
use Stripe\StripeClient;
use Stripe\ThinEvent;
use Stripe\V2\Event;

it('retrieves events with context when available', function () {
    $client = \Mockery::mock(StripeClient::class);
    $v2Factory = \Mockery::mock(V2ServiceFactory::class);
    $coreFactory = \Mockery::mock(CoreServiceFactory::class);
    $eventService = \Mockery::mock(EventService::class);

    $thinEvent = new ThinEvent();
    $thinEvent->id = 'evt_123';
    $thinEvent->context = 'acct_456';
    $thinEvent->type = 'customer.created';

    $event = Event::constructFrom([
        'id' => 'evt_123',
        'object' => 'v2.core.event',
        'type' => 'customer.created',
        'created' => now()->getTimestamp(),
    ], null, 'v2');

    $client->shouldReceive('parseThinEvent')
        ->once()
        ->with('payload', 'sig', 'whsec_test')
        ->andReturn($thinEvent);
    $client->shouldReceive('getService')
        ->once()
        ->with('v2')
        ->andReturn($v2Factory);

    $v2Factory->shouldReceive('getService')
        ->once()
        ->with('core')
        ->andReturn($coreFactory);

    $coreFactory->shouldReceive('getService')
        ->once()
        ->with('events')
        ->andReturn($eventService);

    $eventService->shouldReceive('retrieve')
        ->once()
        ->with('evt_123', null, ['stripe_account' => 'acct_456'])
        ->andReturn($event);

    $processor = new ThinEventProcessor($client, 'whsec_test');

    $result = $processor->handle('payload', 'sig');

    expect($result->thinEvent)->toBe($thinEvent);
    expect($result->event)->toBe($event);
});

it('retrieves events without context using default options', function () {
    $client = \Mockery::mock(StripeClient::class);
    $v2Factory = \Mockery::mock(V2ServiceFactory::class);
    $coreFactory = \Mockery::mock(CoreServiceFactory::class);
    $eventService = \Mockery::mock(EventService::class);

    $thinEvent = new ThinEvent();
    $thinEvent->id = 'evt_456';
    $thinEvent->type = 'payment_intent.succeeded';

    $event = Event::constructFrom([
        'id' => 'evt_456',
        'object' => 'v2.core.event',
        'type' => 'payment_intent.succeeded',
        'created' => now()->getTimestamp(),
    ], null, 'v2');

    $client->shouldReceive('parseThinEvent')
        ->once()
        ->with('payload', 'sig', 'whsec_test')
        ->andReturn($thinEvent);
    $client->shouldReceive('getService')
        ->once()
        ->with('v2')
        ->andReturn($v2Factory);

    $v2Factory->shouldReceive('getService')
        ->once()
        ->with('core')
        ->andReturn($coreFactory);

    $coreFactory->shouldReceive('getService')
        ->once()
        ->with('events')
        ->andReturn($eventService);

    $eventService->shouldReceive('retrieve')
        ->once()
        ->with('evt_456', null, null)
        ->andReturn($event);

    $processor = new ThinEventProcessor($client, 'whsec_test');

    $result = $processor->handle('payload', 'sig');

    expect($result->thinEvent)->toBe($thinEvent);
    expect($result->event)->toBe($event);
});
