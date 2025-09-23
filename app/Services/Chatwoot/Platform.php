<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use RuntimeException;

class Platform extends Service
{
    protected string $platformAccessToken;

    public function __construct(Factory $http, ?string $endpoint = null, ?string $platformAccessToken = null)
    {
        parent::__construct($http, $endpoint);

        $this->platformAccessToken = $this->resolvePlatformAccessToken($platformAccessToken);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getUser(int $accountId, int $userId): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/users/%d', $userId))
            ->throw();

        $user = $response->json();

        if (! is_array($user)) {
            throw new RuntimeException('Chatwoot user response was not valid JSON.');
        }

        if ($accountId > 0 && ! $this->userBelongsToAccount($user, $accountId)) {
            throw new RuntimeException('Chatwoot user does not belong to the specified account.');
        }

        return $user;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function impersonateUser(int $accountId, int $userId): Application
    {
        $user = $this->getUser($accountId, $userId);

        $authToken = $this->extractAuthToken($user);

        return new Application($authToken, $this->http, $this->endpoint);
    }

    protected function extractAuthToken(array $payload): ?string
    {
        $candidates = [
            Arr::get($payload, 'access_token'),
            Arr::get($payload, 'auth_token'),
            Arr::get($payload, 'token'),
            Arr::get($payload, 'user.auth_token'),
            $this->extractAuthTokenFromSsoLink($payload),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function sendMessageAsUser(
        int $accountId,
        int $userId,
        int $conversationId,
        string $content,
        array $attributes = []
    ): array {
        return $this->impersonateUser($accountId, $userId)
            ->sendMessage($accountId, $conversationId, $content, $attributes);
    }

    protected function request(): PendingRequest
    {
        return $this->authorizedRequest($this->platformAccessToken);
    }

    protected function userBelongsToAccount(array $user, int $accountId): bool
    {
        $accounts = $user['accounts'] ?? [];

        if (! is_array($accounts)) {
            return false;
        }

        foreach ($accounts as $account) {
            if (! is_array($account)) {
                continue;
            }

            if ((int) ($account['id'] ?? 0) === $accountId) {
                return true;
            }
        }

        return false;
    }

    protected function extractAuthTokenFromSsoLink(array $payload): ?string
    {
        $ssoLink = Arr::get($payload, 'sso_link');

        if (! is_string($ssoLink) || $ssoLink === '') {
            return null;
        }

        $parts = parse_url($ssoLink);

        if (! is_array($parts) || ! isset($parts['query'])) {
            return null;
        }

        parse_str($parts['query'], $query);

        $token = $query['auth_token'] ?? $query['token'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    protected function resolvePlatformAccessToken(?string $platformAccessToken): string
    {
        $platformAccessToken ??= (string) ($this->config['platform_access_token'] ?? '');

        if ($platformAccessToken === '') {
            throw new RuntimeException('Chatwoot platform access token is not configured.');
        }

        return $platformAccessToken;
    }

}
