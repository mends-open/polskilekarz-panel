<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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

    public function sendMessageAsUser(int $accountId, int $userId, int $conversationId, string $content, array $attributes = []): array
    {
        return $this->impersonateUser($accountId, $userId)
            ->sendMessage($accountId, $conversationId, $content, $attributes);
    }

    protected function impersonateUser(int $accountId, int $userId): Application
    {
        $user = $this->getUser($accountId, $userId);

        $accessToken = $this->resolveAccessToken($user, $accountId, $userId);

        return new Application($accessToken, $this->http, $this->endpoint);
    }

    private function getUser(int $accountId, int $userId): array
    {
        $response = $this->request()->get(sprintf('platform/api/v1/users/%d', $userId));

        if ($response->successful()) {
            return $this->validateUserPayload($response->json(), $accountId, $userId);
        }

        if ($response->status() !== 404) {
            $response->throw();
        }

        $fallback = $this->request()
            ->get(sprintf('platform/api/v1/accounts/%d/users/%d', $accountId, $userId))
            ->throw();

        return $this->validateUserPayload($fallback->json(), $accountId, $userId);
    }

    private function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'api_access_token' => $this->platformAccessToken,
            ]);
    }

    /**
     * @param  array<string, mixed>  $user
     */
    private function userBelongsToAccount(array $user, int $accountId): bool
    {
        $accounts = $user['accounts'] ?? [];

        if (! is_array($accounts)) {
            return false;
        }

        foreach ($accounts as $account) {
            if (is_array($account) && (int) ($account['id'] ?? 0) === $accountId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $user
     */
    private function resolveAccessToken(array $user, int $accountId, int $userId): string
    {
        $token = $this->extractToken($user);

        if ($token !== null) {
            return $token;
        }

        $loginPayload = $this->loginUser($accountId, $userId);

        $token = $this->extractToken($loginPayload);

        if ($token === null) {
            throw new RuntimeException('Chatwoot login response did not include an access token.');
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractToken(array $payload): ?string
    {
        foreach (['access_token', 'auth_token'] as $key) {
            $token = $payload[$key] ?? null;

            if (is_string($token) && $token !== '') {
                return $token;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function loginUser(int $accountId, int $userId): array
    {
        $endpoints = [
            ['url' => sprintf('platform/api/v1/users/%d/login', $userId), 'payload' => ['account_id' => $accountId]],
            ['url' => sprintf('platform/api/v1/accounts/%d/users/%d/login', $accountId, $userId), 'payload' => []],
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = $this->request()
                    ->post($endpoint['url'], $endpoint['payload'])
                    ->throw();

                $data = $response->json();

                if (is_array($data)) {
                    return $data;
                }

                throw new RuntimeException('Chatwoot login response was not valid JSON.');
            } catch (RequestException $exception) {
                if ($exception->response?->status() === 404) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new RuntimeException('Unable to impersonate Chatwoot user; login endpoint was not found.');
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function validateUserPayload($payload, int $accountId, int $userId): array
    {
        if (! is_array($payload)) {
            throw new RuntimeException('Chatwoot user response was not valid JSON.');
        }

        if (! $this->userBelongsToAccount($payload, $accountId)) {
            throw new RuntimeException(sprintf(
                'User %d does not belong to Chatwoot account %d.',
                $userId,
                $accountId,
            ));
        }

        return $payload;
    }
}
