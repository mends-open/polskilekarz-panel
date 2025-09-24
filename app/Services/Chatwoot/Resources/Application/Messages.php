<?php

namespace App\Services\Chatwoot\Resources\Application;

use App\Services\Chatwoot\Application;
use App\Services\Chatwoot\Resources\Resource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class Messages extends Resource
{
    public function __construct(private readonly Application $application)
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(int $accountId, int $conversationId, string $content, array $attributes = []): array
    {
        $payload = array_merge(['message_type' => 'outgoing'], $attributes);

        $payload['content'] = $content;

        if (isset($payload['private'])) {
            $payload['private'] = (bool) $payload['private'];
        }

        if (isset($payload['content_type']) && $payload['content_type'] instanceof \BackedEnum) {
            $payload['content_type'] = $payload['content_type']->value;
        }

        if (isset($payload['message_type']) && $payload['message_type'] instanceof \BackedEnum) {
            $payload['message_type'] = $payload['message_type']->value;
        }

        if (! isset($payload['message_type']) || ! is_string($payload['message_type']) || $payload['message_type'] === '') {
            $payload['message_type'] = 'outgoing';
        }

        $response = $this->request()
            ->post(
                sprintf('api/v1/accounts/%d/conversations/%d/messages', $accountId, $conversationId),
                $payload,
            )
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot message response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->application->request();
    }
}
