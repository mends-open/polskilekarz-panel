<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
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
            [
                'method' => 'post',
                'path' => sprintf('platform/api/v1/users/%d/token', $userId),
            ],
            [
                'method' => 'post',
                'path' => sprintf('platform/api/v1/accounts/%d/users/%d/token', $accountId, $userId),
            ],
            [
                'method' => 'post',
                'path' => sprintf('platform/api/v1/accounts/%d/users/%d/login', $accountId, $userId),
                'keys' => ['auth_token'],
            ],
            [
                'method' => 'get',
                'path' => sprintf('platform/api/v1/accounts/%d/users/%d/login', $accountId, $userId),
                'keys' => ['auth_token'],
            ],
        ];

        $lastException = null;

        foreach ($tokenEndpoints as $endpoint) {
            $method = $endpoint['method'];
            $path = $endpoint['path'];

            try {
                $response = $this->request()
                    ->{$method}($path)
                    ->throw();
            } catch (RequestException $exception) {
                $lastException = $exception;

                continue;
            }

            $accessToken = $this->extractAccessToken($response, $endpoint['keys'] ?? ['access_token', 'auth_token']);

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

    /**
     * @param  array<int, string>  $keys
     */
    protected function extractAccessToken(Response $response, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $response->json($key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->platformAccessToken);
    }
}
