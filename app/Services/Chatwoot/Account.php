<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class Account
{
    protected Factory $http;

    protected string $endpoint;

    protected string $platformAccessToken;

    public function __construct(string $platformAccessToken, Factory $http, ?string $endpoint = null)
    {
        $this->http = $http;

        if ($endpoint === null) {
            $endpoint = function_exists('config')
                ? (string) (config('services.chatwoot.endpoint') ?? '')
                : '';
        }

        $this->endpoint = rtrim($endpoint, '/');
        $this->platformAccessToken = $platformAccessToken;
    }

    public function getUserAccessToken(int $accountId, int $userId): string
    {
        $tokenEndpoints = [
            sprintf('platform/api/v1/users/%d/token', $userId),
            sprintf('platform/api/v1/accounts/%d/users/%d/token', $accountId, $userId),
        ];

        $lastException = null;

        foreach ($tokenEndpoints as $endpoint) {
            try {
                $response = $this->request()
                    ->post($endpoint)
                    ->throw();
            } catch (RequestException $exception) {
                $lastException = $exception;

                continue;
            }

            $accessToken = $response->json('access_token') ?? $response->json('auth_token');

            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Chatwoot impersonation response did not include an access token.');
            }

            return $accessToken;
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException('Unable to retrieve Chatwoot user token.');
    }

    protected function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->platformAccessToken);
    }
}
