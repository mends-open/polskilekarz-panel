<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class Platform
{
    protected Factory $http;

    protected string $endpoint;

    protected string $platformAccessToken;

    protected string $applicationAccessToken;

    public function __construct(Factory $http, ?string $endpoint = null, ?string $platformAccessToken = null, ?string $applicationAccessToken = null)
    {
        $this->http = $http;

        $config = [];

        if ($endpoint === null || $platformAccessToken === null || $applicationAccessToken === null) {
            if (function_exists('config')) {
                try {
                    $config = config('services.chatwoot', []);
                } catch (Throwable) {
                    $config = [];
                }
            }

            $endpoint ??= (string) ($config['endpoint'] ?? '');

            if ($platformAccessToken === null) {
                $token = (string) ($config['platform_access_token'] ?? '');

                if ($token === '' && isset($config['api_access_token'])) {
                    $token = (string) $config['api_access_token'];
                }

                $platformAccessToken = $token;
            }

            if ($applicationAccessToken === null) {
                $applicationAccessToken = (string) ($config['api_access_token'] ?? $platformAccessToken ?? '');
            }
        }

        $this->endpoint = rtrim($endpoint ?? '', '/');
        $this->platformAccessToken = $platformAccessToken ?? '';
        $this->applicationAccessToken = $applicationAccessToken ?? '';
    }

    public function getUser(int $accountId, int $userId): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/users/%d', $userId))
            ->throw();

        return $response->json();
    }

    public function impersonateUser(int $accountId, int $userId): Application
    {
        $response = $this->impersonationRequest($accountId, $userId);

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Chatwoot impersonation response did not include an access token.');
        }

        return new Application(
            $accessToken,
            $this->http,
            $this->endpoint,
            $this->applicationAccessToken !== '' ? $this->applicationAccessToken : $this->platformAccessToken,
        );
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
            ->withToken($this->platformAccessToken)
            ->withHeaders(['api_access_token' => $this->platformAccessToken]);
    }

    protected function impersonationRequest(int $accountId, int $userId)
    {
        try {
            return $this->request()
                ->post(sprintf('platform/api/v1/users/%d/token', $userId))
                ->throw();
        } catch (RequestException $exception) {
            if (! $this->shouldProvisionUser($exception)) {
                throw $exception;
            }

            $this->provisionUser($accountId, $userId);

            return $this->request()
                ->post(sprintf('platform/api/v1/users/%d/token', $userId))
                ->throw();
        }
    }

    protected function shouldProvisionUser(RequestException $exception): bool
    {
        $response = $exception->response;

        if ($response === null || $response->status() !== 401) {
            return false;
        }

        return $response->json('error') === 'Non permissible resource';
    }

    protected function provisionUser(int $accountId, int $userId): void
    {
        if ($this->applicationAccessToken === '') {
            throw new RuntimeException('Chatwoot application access token is required to provision users for impersonation.');
        }

        $agent = $this->applicationClient()->getAgent($accountId, $userId);

        $email = $agent['email'] ?? null;

        if (! is_string($email) || $email === '') {
            throw new RuntimeException(sprintf('Chatwoot agent %d did not include an email address.', $userId));
        }

        $displayName = $agent['available_name'] ?? $agent['name'] ?? null;

        $payload = array_filter([
            'email' => $email,
            'name' => is_string($displayName) && $displayName !== '' ? $displayName : null,
            'display_name' => is_string($displayName) && $displayName !== '' ? $displayName : null,
        ]);

        $this->request()
            ->post('platform/api/v1/users', $payload)
            ->throw();
    }

    protected function applicationClient(): Application
    {
        return new Application(
            $this->applicationAccessToken,
            $this->http,
            $this->endpoint,
            $this->applicationAccessToken !== '' ? $this->applicationAccessToken : $this->platformAccessToken,
        );
    }
}
