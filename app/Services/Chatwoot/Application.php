<?php

namespace App\Services\Chatwoot;

use App\Enums\Chatwoot\ContentType;
use App\Enums\Chatwoot\MessagePrivacy;
use App\Enums\Chatwoot\MessageType;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class Application extends Service
{
    protected string $authToken;

    public function __construct(?string $authToken, Factory $http, ?string $endpoint = null)
    {
        parent::__construct($http, $endpoint);

        $this->authToken = $this->resolveAuthToken($authToken);
    }

    public function sendMessage(int $accountId, int $conversationId, string $content, array $attributes = []): array
    {
        $payload = $this->normalisePayload(array_merge([
            'message_type' => MessageType::Outgoing,
        ], $attributes));

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
        return $this->authorizedRequest($this->authToken);
    }

    protected function resolveAuthToken(?string $authToken): string
    {
        if (is_string($authToken) && $authToken !== '') {
            return $authToken;
        }

        $fallback = (string) ($this->config['fallback_access_token'] ?? '');

        if ($fallback === '') {
            throw new RuntimeException('Chatwoot application access token is not configured.');
        }

        return $fallback;
    }

    protected function normalisePayload(array $payload): array
    {
        $payload['message_type'] = $this->normaliseMessageType($payload);
        $payload = $this->normaliseContentType($payload);
        $payload['private'] = $this->normalisePrivacyFlag($payload);

        return $payload;
    }

    protected function normaliseMessageType(array $payload): string
    {
        $type = $payload['message_type'] ?? MessageType::Outgoing;

        if ($type instanceof MessageType) {
            return $type->value;
        }

        if (is_string($type) && $type !== '') {
            return $type;
        }

        return MessageType::Outgoing->value;
    }

    protected function normaliseContentType(array $payload): array
    {
        if (isset($payload['content_type']) && $payload['content_type'] instanceof ContentType) {
            $payload['content_type'] = $payload['content_type']->value;
        }

        return $payload;
    }

    protected function normalisePrivacyFlag(array $payload): bool
    {
        $privacy = $payload['private'] ?? MessagePrivacy::Public;

        if ($privacy instanceof MessagePrivacy) {
            return $privacy->toPayload();
        }

        if (is_bool($privacy)) {
            return $privacy;
        }

        return (bool) $privacy;
    }

}
