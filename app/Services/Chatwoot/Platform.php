<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class Platform
{
    protected Factory $http;

    protected string $endpoint;

    protected string $platformAccessToken;

    public function __construct(Factory $http, ?string $endpoint = null, ?string $platformAccessToken = null)
    {
        $this->http = $http;

        if ($endpoint === null || $platformAccessToken === null) {
            $config = function_exists('config') ? config('services.chatwoot', []) : [];
            $endpoint ??= (string) ($config['endpoint'] ?? '');
            $platformAccessToken ??= (string) ($config['platform_access_token'] ?? '');
        }

        $this->endpoint = rtrim($endpoint ?? '', '/');
        $this->platformAccessToken = $platformAccessToken ?? '';
    }

    public function getUser(int $accountId, int $userId): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/accounts/%d/users/%d', $accountId, $userId))
            ->throw();

        return $response->json();
    }

    public function impersonateUser(int $accountId, int $userId): Application
    {
        $response = $this->request()
            ->post(sprintf('platform/api/v1/accounts/%d/users/%d/login', $accountId, $userId))
            ->throw();

        $authToken = $response->json('auth_token');

        if (! is_string($authToken) || $authToken === '') {
            throw new RuntimeException('Chatwoot impersonation response did not include an auth token.');
        }

        return new Application($authToken, $this->http, $this->endpoint);
    }

    public function sendMessageAsUser(int $accountId, int $userId, int $conversationId, string $content, array $attributes = []): array
    {
        return $this->impersonateUser($accountId, $userId)
            ->sendMessage($accountId, $conversationId, $content, $attributes);
    }

    protected function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->platformAccessToken);
    }
}
