<?php

declare(strict_types=1);

use App\Services\Chatwoot\Application;
use App\Services\Chatwoot\Platform;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;

it('retrieves a user by id from the Chatwoot platform API', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/accounts/1/users/2' => Factory::response(['id' => 2], 200),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $user = $platform->getUser(1, 2);

    expect($user)->toBe(['id' => 2]);

    expect($http->recorded())->toHaveCount(1);

    $request = $http->recorded()[0][0];

    expect($request->method())->toBe('GET');
    expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/accounts/1/users/2');
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
    expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();
});

it('falls back to the account scoped token endpoint when the user endpoint is unavailable', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/55/token') {
            return Factory::response(['error' => 'Not Found'], 404);
        }

        expect($request->url())->toBe('https://chatwoot.test/platform/api/v1/accounts/77/users/55/token');
        expect($request->method())->toBe('POST');
        expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();

        return Factory::response(['access_token' => 'user-token'], 200);
    });

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    $application = $platform->impersonateUser(77, 55);

    expect($application)->toBeInstanceOf(Application::class);

    expect($http->recorded())->toHaveCount(2);

    $firstRequest = $http->recorded()[0][0];

    expect($firstRequest->url())->toBe('https://chatwoot.test/platform/api/v1/users/55/token');
    expect($firstRequest->method())->toBe('POST');
});

it('sends a message on behalf of an impersonated user', function () {
    $http = new Factory();

    $http->fake(function (Request $request) {
        if ($request->url() === 'https://chatwoot.test/platform/api/v1/users/15/token') {
            expect($request->method())->toBe('POST');
            expect($request->hasHeader('Authorization', 'Bearer platform-token'))->toBeTrue();

            return Factory::response(['access_token' => 'user-token'], 200);
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

it('propagates the last impersonation error when all token endpoints fail', function () {
    $http = new Factory();

    $http->fake([
        'https://chatwoot.test/platform/api/v1/users/42/token' => Factory::response(['error' => 'Non permissible resource'], 401),
        'https://chatwoot.test/platform/api/v1/accounts/4/users/42/token' => Factory::response(null, 403),
    ]);

    $platform = new Platform($http, 'https://chatwoot.test', 'platform-token');

    expect(fn () => $platform->impersonateUser(4, 42))->toThrow(RequestException::class);
});
