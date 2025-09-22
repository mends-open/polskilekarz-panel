<?php

declare(strict_types=1);

use App\Services\Chatwoot\Platform;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;

it('sends a message on behalf of an impersonated user', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/15') {
            expect($request->method())->toBe('GET');
            expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();

            return Factory::response([
                'id' => 15,
                'access_token' => 'user-token',
                'accounts' => [
                    ['id' => 5],
                ],
            ], 200);
        }

        expect($request->url())->toBe('https://chatwoot.test/api/v1/accounts/5/conversations/25/messages');
        expect($request->method())->toBe('POST');
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

it('propagates impersonation errors from the platform API', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/42' => Factory::response(['error' => 'Non permissible resource'], 401),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(4, 42, 55, 'Hello'))->toThrow(RequestException::class);
});

it('throws when the user does not belong to the account', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/45' => Factory::response([
            'id' => 45,
            'access_token' => 'user-token',
            'accounts' => [
                ['id' => 99],
            ],
        ], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(5, 45, 77, 'Hello'))->toThrow(RuntimeException::class);
});

it('throws when the user payload is missing an access token', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/88' => Factory::response([
            'id' => 88,
            'accounts' => [
                ['id' => 9],
            ],
        ], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(9, 88, 66, 'Hello'))->toThrow(RuntimeException::class);
});
