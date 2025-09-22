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
            expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();

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

it('falls back to the account scoped user endpoint when the global lookup fails', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/30') {
            return Factory::response(['error' => 'Not Found'], 404);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/accounts/3/users/30') {
            expect($request->method())->toBe('GET');
            expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();

            return Factory::response([
                'id' => 30,
                'access_token' => 'user-token',
                'accounts' => [
                    ['id' => 3],
                ],
            ], 200);
        }

        expect($request->url())->toBe('https://chatwoot.test/api/v1/accounts/3/conversations/40/messages');

        return Factory::response(['id' => 101], 201);
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $response = $platform->sendMessageAsUser(3, 30, 40, 'Fallback message');

    expect($response)->toBe(['id' => 101]);
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

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/88') {
            return Factory::response([
                'id' => 88,
                'accounts' => [
                    ['id' => 9],
                ],
            ], 200);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/88/login') {
            return Factory::response(['access_token' => 'user-token'], 200);
        }

        throw new RuntimeException('Unexpected request');
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(9, 88, 66, 'Hello'))->toThrow(RuntimeException::class);
});

it('falls back to the account scoped login endpoint when the global login route is missing', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/91') {
            return Factory::response([
                'id' => 91,
                'accounts' => [
                    ['id' => 11],
                ],
            ], 200);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/91/login') {
            return Factory::response(['error' => 'Not Found'], 404);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/accounts/11/users/91/login') {
            expect($request->method())->toBe('POST');
            expect($request->hasHeader('api_access_token', 'platform-token'))->toBeTrue();

            return Factory::response(['auth_token' => 'user-token'], 200);
        }

        expect($request->url())->toBe('https://chatwoot.test/api/v1/accounts/11/conversations/77/messages');

        return Factory::response(['id' => 555], 201);
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $response = $platform->sendMessageAsUser(11, 91, 77, 'Hello');

    expect($response)->toBe(['id' => 555]);
});

it('throws when no access token can be derived from the login endpoint', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/93') {
            return Factory::response([
                'id' => 93,
                'accounts' => [
                    ['id' => 12],
                ],
            ], 200);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/93/login') {
            return Factory::response(['token' => ''], 200);
        }

        if ($request->url() === 'https://chatwoot.test/platform/api/v1/accounts/12/users/93/login') {
            return Factory::response(['token' => ''], 200);
        }

        throw new RuntimeException('Unexpected request');
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(12, 93, 55, 'Hello'))->toThrow(RuntimeException::class, 'Chatwoot login response did not include an access token.');
});

it('throws when login endpoints are missing', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/94') {
            return Factory::response([
                'id' => 94,
                'accounts' => [
                    ['id' => 13],
                ],
            ], 200);
        }

        if (str_contains($request->url(), '/login')) {
            return Factory::response(['error' => 'Not Found'], 404);
        }

        throw new RuntimeException('Unexpected request');
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->sendMessageAsUser(13, 94, 44, 'Hello'))->toThrow(RuntimeException::class, 'Unable to impersonate Chatwoot user; login endpoint was not found.');
});
