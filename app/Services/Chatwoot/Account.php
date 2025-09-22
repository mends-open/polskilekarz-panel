<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

class Account
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

    protected function request(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withToken($this->authToken);
    }
}
