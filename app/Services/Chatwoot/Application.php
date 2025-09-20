<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class Application
{
    protected Factory $http;

    protected string $endpoint;

    protected string $authToken;

    public function __construct(string $authToken, Factory $http, ?string $endpoint = null)
    {
        $this->http = $http;

        if ($endpoint === null) {
            $endpoint = function_exists('config')
                ? (string) (config('services.chatwoot.endpoint') ?? '')
                : '';
        }

        $this->endpoint = rtrim($endpoint, '/');
        $this->authToken = $authToken;
    }

    public function sendMessage(int $accountId, int $conversationId, string $content, array $attributes = []): array
    {
        $payload = array_merge(['message_type' => 'outgoing'], $attributes);
        $payload['content'] = $content;

        $response = $this->request()
            ->post(sprintf('api/v1/accounts/%d/conversations/%d/messages', $accountId, $conversationId), $payload)
            ->throw();

        return $response->json();
    }

    public function getAgent(int $accountId, int $userId): array
    {
        $response = $this->request()
            ->get(sprintf('api/v1/accounts/%d/agents', $accountId), [
                'page' => 1,
                'per_page' => 100,
            ])
            ->throw();

        $agents = $response->json();

        if (! is_array($agents)) {
            throw new RuntimeException('Chatwoot agents response was not an array.');
        }

        foreach ($agents as $agent) {
            if ((int) ($agent['id'] ?? 0) === $userId) {
                return $agent;
            }
        }

        throw new RuntimeException(sprintf(
            'Agent %d was not found in Chatwoot account %d.',
            $userId,
            $accountId,
        ));
    }

    protected function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->authToken);
    }
}
