<?php

declare(strict_types=1);

use App\Services\Chatwoot\Application;
use App\Services\Chatwoot\Platform;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;

it('retrieves a user by id from the Chatwoot platform API', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/2' => Factory::response(['id' => 2], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $user = $platform->getUser(1, 2);

    expect($user)->toBe(['id' => 2]);

    expect($http->recorded())->toHaveCount(1);

    $request = $http->recorded()[0][0];

    expect($request->method())->toBe('GET');
    expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/users/2');
    expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();
    expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();
});

it('impersonates a user and returns an application client', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/20/token' => Factory::response(['access_token' => 'user-token'], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $application = $platform->impersonateUser(10, 20);

    expect($application)->toBeInstanceOf(Application::class);

    expect($http->recorded())->toHaveCount(1);

    $request = $http->recorded()[0][0];

    expect($request->method())->toBe('POST');
    expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/users/20/token');
    expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();
    expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();
});

it('sends a message on behalf of an impersonated user', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/15/token') {
            expect($request->method())->toBe('POST');
            expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();
            expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();

            return Factory::response(['access_token' => 'user-token'], 200);
        }

        expect($request->url())->toBe('https://chatwoot.test/api/v1/accounts/5/conversations/25/messages');
        expect($request->method())->toBe('POST');
        expect($request->hasHeader('api_access_token', 'user-token'))->toBeTrue();
        expect($request->hasHeader('Authorization', 'Bearer user-token'))->toBeTrue();
        expect($request->data())->toMatchArray([
            'content' => 'Hello from Chatwoot',
            'message_type' => 'outgoing',
            'private' => true,
        ]);

        return Factory::response(['id' => 99], 201);
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $response = $platform->sendMessageAsUser(5, 15, 25, 'Hello from Chatwoot', [
        'private' => true,
    ]);

    expect($response)->toBe(['id' => 99]);

    expect($http->recorded())->toHaveCount(2);
});

it('provisions a user when impersonation fails with non permissible resource', function () {
    $http = new Factory();

    $tokenRequests = 0;

    $http->fake(function (Request $request) use (&$tokenRequests) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/33/token') {
            $tokenRequests++;

            expect($request->method())->toBe('POST');
            expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();
            expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();

            if ($tokenRequests === 1) {
                return Factory::response(['error' => 'Non permissible resource'], 401);
            }

            return Factory::response(['access_token' => 'user-token'], 200);
        }

        if (str_contains($request->url(), 'https://chatwoot.test/api/v1/accounts/5/agents')) {
            expect($request->method())->toBe('GET');
            expect($request->hasHeader('api_access_token', 'api-token'))->toBeTrue();
            expect($request->hasHeader('Authorization', 'Bearer api-token'))->toBeTrue();

            return Factory::response([
                ['id' => 33, 'email' => 'agent@example.com', 'available_name' => 'Agent Example'],
            ], 200);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users') {
            expect($request->method())->toBe('POST');
            expect($request->data())->toMatchArray([
                'email' => 'agent@example.com',
                'name' => 'Agent Example',
                'display_name' => 'Agent Example',
            ]);

            return Factory::response(['id' => 33], 200);
        }

        if ($request->url() === 'https://chatwoot.test/api/v1/accounts/5/conversations/25/messages') {
            expect($request->method())->toBe('POST');
            expect($request->hasHeader('api_access_token', 'user-token'))->toBeTrue();
            expect($request->hasHeader('Authorization', 'Bearer user-token'))->toBeTrue();

            return Factory::response(['id' => 101], 201);
        }

        throw new RuntimeException('Unexpected request: '.$request->url());
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token', 'api-token');

    $response = $platform->sendMessageAsUser(5, 33, 25, 'Follow-up message');

    expect($response)->toBe(['id' => 101]);

    expect($tokenRequests)->toBe(2);
});

it('falls back to the API access token when the platform token is missing', function () {
    $originalContainer = Container::getInstance();
    $container = new Container();
    Container::setInstance($container);

    try {
        $container->instance('config', new Repository([
            'services' => [
                'chatwoot' => [
                    'endpoint' => 'https://chatwoot.test',
                    'platform_access_token' => '',
                    'api_access_token' => 'api-token',
                ],
            ],
        ]));

        $http = new Factory();

        $http->fake([
            'https://chatwoot.test/platform/api/v1/users/2' => Factory::response(['id' => 2], 200),
        ]);

        $platform = new Platform($http);

        $platform->getUser(1, 2);

        $request = $http->recorded()[0][0];

        expect($request->hasHeader('api_access_token', 'api-token'))->toBeTrue();
        expect($request->hasHeader('Authorization', 'Bearer api-token'))->toBeTrue();
    } finally {
        Container::setInstance($originalContainer);
    }
});
