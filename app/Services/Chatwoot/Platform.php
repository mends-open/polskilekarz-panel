<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use RuntimeException;
use Throwable;

class Platform
{
    protected Factory $http;

    protected string $endpoint;

    protected string $platformAccessToken;

    public function __construct(Factory $http, ?string $endpoint = null, ?string $platformAccessToken = null)
    {
        $this->http = $http;

        $config = $this->configuration();

        if ($endpoint === null) {
            $endpoint = (string) $config['endpoint'];
        }

        if ($platformAccessToken === null) {
            $platformAccessToken = (string) $config['platform_access_token'];
        }

        $this->endpoint = rtrim($endpoint ?? '', '/');
        $this->platformAccessToken = $platformAccessToken ?? '';

        if ($this->platformAccessToken === '') {
            throw new RuntimeException('Chatwoot platform access token is not configured.');
        }
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

        if ($accountId > 0) {
            $accounts = $user['accounts'] ?? [];

            $belongsToAccount = false;

            if (is_array($accounts)) {
                foreach ($accounts as $account) {
                    if (is_array($account) && (int) ($account['id'] ?? 0) === $accountId) {
                        $belongsToAccount = true;
                        break;
                    }
                }
            }

            if (! $belongsToAccount) {
                throw new RuntimeException('Chatwoot user does not belong to the specified account.');
            }
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

        if ($authToken === null || $authToken === '') {
            $authToken = null;
        }

        return new Application($authToken, $this->http, $this->endpoint);
    }

    protected function extractAuthToken(array $payload): ?string
    {
        $authToken = $payload['access_token']
            ?? $payload['auth_token']
            ?? $payload['token']
            ?? null;

        if (! is_string($authToken) || $authToken === '') {
            $authToken = Arr::get($payload, 'user.auth_token');
        }

        if (! is_string($authToken) || $authToken === '') {
            $ssoLink = $payload['sso_link'] ?? null;

            if (is_string($ssoLink) && $ssoLink !== '') {
                $parts = parse_url($ssoLink);

                if (is_array($parts) && isset($parts['query'])) {
                    parse_str($parts['query'], $query);
                    $authToken = $query['auth_token'] ?? $query['token'] ?? null;
                }
            }
        }

        if (! is_string($authToken) || $authToken === '') {
            return null;
        }

        return $authToken;
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
        $application = $this->impersonateUser($accountId, $userId);

        return $application->sendMessage($accountId, $conversationId, $content, $attributes);
    }

    protected function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'api_access_token' => $this->platformAccessToken,
            ]);
    }

    protected function configuration(): array
    {
        if (! function_exists('config')) {
            return [];
        }

        try {
            $config = config('services.chatwoot', []);
        } catch (Throwable) {
            return [];
        }

        return is_array($config) ? $config : [];
    }

}
