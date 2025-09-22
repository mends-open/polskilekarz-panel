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

    public function sendMessageAsUser(int $accountId, int $userId, int $conversationId, string $content, array $attributes = []): array
    {
        return $this->impersonateUser($accountId, $userId)
            ->sendMessage($accountId, $conversationId, $content, $attributes);
    }

    protected function impersonateUser(int $accountId, int $userId): Application
    {
        $user = $this->getUser($accountId, $userId);

        $accessToken = $user['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Chatwoot user response did not include an access token.');
        }

        return new Application($accessToken, $this->http, $this->endpoint);
    }

    private function getUser(int $accountId, int $userId): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/users/%d', $userId))
            ->throw();

        $user = $response->json();

        if (! is_array($user)) {
            throw new RuntimeException('Chatwoot user response was not valid JSON.');
        }

        if (! $this->userBelongsToAccount($user, $accountId)) {
            throw new RuntimeException(sprintf(
                'User %d does not belong to Chatwoot account %d.',
                $userId,
                $accountId,
            ));
        }

        return $user;
    }

    private function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->platformAccessToken);
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
}
