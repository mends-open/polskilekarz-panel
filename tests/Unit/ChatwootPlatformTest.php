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
        'https://chatwoot.test/platform/api/v1/users/2' => Factory::response([
            'id' => 2,
            'accounts' => [
                ['id' => 1],
            ],
        ], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $user = $platform->getUser(1, 2);

    expect($user)->toMatchArray([
        'id' => 2,
        'accounts' => [
            ['id' => 1],
        ],
    ]);

    expect($http->recorded())->toHaveCount(1);

    $request = $http->recorded()[0][0];

    expect($request->method())->toBe('GET');
    expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/users/2');
    expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();
});

it('impersonates a user using the embedded access token', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/42' => Factory::response([
            'id' => 42,
            'accounts' => [
                ['id' => 7],
            ],
            'access_token' => 'user-token',
        ], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $application = $platform->impersonateUser(7, 42);

    expect($application)->toBeInstanceOf(Application::class);

    expect($http->recorded())->toHaveCount(1);

    $request = $http->recorded()[0][0];

    expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/users/42');
    expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();
});

it('sends a message using the access token embedded in the user payload', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/15') {
            expect($request->method())->toBe('GET');
            expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();

            return Factory::response([
                'id' => 15,
                'accounts' => [
                    ['id' => 5],
                ],
                'access_token' => 'user-token',
            ], 200);
        }

        expect($request->url())->toBe('https://chatwoot.test/api/v1/accounts/5/conversations/25/messages');
        expect($request->method())->toBe('POST');
        expect($request->hasHeader('api_access_token', 'user-token'))->toBeTrue();
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

it('throws when the user payload does not contain an access token', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/2' => Factory::response([
            'id' => 2,
            'accounts' => [
                ['id' => 1],
            ],
        ], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(1, 2, 3, 'No token'))->toThrow(\RuntimeException::class, 'access token');
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
            'https://chatwoot.test/platform/api/v1/users/2' => Factory::response([
                'id' => 2,
                'accounts' => [
                    ['id' => 1],
                ],
            ], 200),
        ]);

        $platform = new Platform($http);

        $platform->getUser(1, 2);

        $request = $http->recorded()[0][0];

        expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/users/2');
        expect($request->hasHeader('api_access_token', 'api-token'))->toBeTrue();
    } finally {
        Container::setInstance($originalContainer);
    }
});
