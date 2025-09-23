<?php

namespace App\Services\Chatwoot;

use App\Enums\Chatwoot\ContentType;
use App\Enums\Chatwoot\MessageType;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;
use Throwable;

class Application
{
    protected Factory $http;

    protected string $endpoint;

    protected string $authToken;

    public function __construct(?string $authToken, Factory $http, ?string $endpoint = null)
    {
        $this->http = $http;

        $config = $this->configuration();

        if ($endpoint === null) {
            $endpoint = (string) $config['endpoint'];
        }

        $this->endpoint = rtrim($endpoint, '/');
        $this->authToken = $this->resolveAuthToken($authToken, $config);
    }

    public function sendMessage(int $accountId, int $conversationId, string $content, array $attributes = []): array
    {
        $payload = array_merge(['message_type' => MessageType::Outgoing], $attributes);
        $payload = $this->normalisePayload($payload);
        $payload['content'] = $content;

        $response = $this->request()
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
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'api_access_token' => $this->authToken,
            ]);
    }

    protected function resolveAuthToken(?string $authToken, array $config): string
    {
        if (is_string($authToken) && $authToken !== '') {
            return $authToken;
        }

        $fallback = (string) ($config['fallback_access_token']);

        if ($fallback === '') {
            throw new RuntimeException('Chatwoot application access token is not configured.');
        }

        return $fallback;
    }

    protected function normalisePayload(array $payload): array
    {
        if (isset($payload['message_type']) && $payload['message_type'] instanceof MessageType) {
            $payload['message_type'] = $payload['message_type']->value;
        } elseif (! isset($payload['message_type']) || $payload['message_type'] === '') {
            $payload['message_type'] = MessageType::Outgoing->value;
        }

        if (isset($payload['content_type']) && $payload['content_type'] instanceof ContentType) {
            $payload['content_type'] = $payload['content_type']->value;
        }

        return $payload;
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
