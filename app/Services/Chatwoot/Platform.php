<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
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

            if ($platformAccessToken === null) {
                $token = (string) ($config['platform_access_token'] ?? '');

                if ($token === '' && isset($config['api_access_token'])) {
                    $token = (string) $config['api_access_token'];
                }

                $platformAccessToken = $token;
            }
        }

        $this->endpoint = rtrim($endpoint ?? '', '/');
        $this->platformAccessToken = $platformAccessToken ?? '';
    }

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

    public function sendMessageAsUser(int $accountId, int $userId, int $conversationId, string $content, array $attributes = [])
    : array
    {
        $user = $this->getUser($accountId, $userId);

        $authToken = $this->extractAuthToken($user);

        if ($authToken === null) {
            throw new RuntimeException('Chatwoot user did not include an access token.');
        }

        $payload = array_merge(['message_type' => 'outgoing'], $attributes);
        $payload['content'] = $content;

        $response = $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($authToken)
            ->post(
                sprintf('api/v1/accounts/%d/conversations/%d/messages', $accountId, $conversationId),
                $payload,
            )
            ->throw();

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Chatwoot message response was not valid JSON.');
        }

        return $data;
    }

    protected function request(): PendingRequest
    {
        if ($this->platformAccessToken === '') {
            throw new RuntimeException('Chatwoot platform access token is not configured.');
        }

        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->platformAccessToken)
            ->withHeaders([
                'api_access_token' => $this->platformAccessToken,
            ])
            ->withQueryParameters([
                'api_access_token' => $this->platformAccessToken,
            ]);
    }
}
