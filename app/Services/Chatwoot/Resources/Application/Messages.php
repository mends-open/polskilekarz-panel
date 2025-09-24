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
    public function list(int $accountId, int $conversationId): array
    {
        $response = $this->request()
            ->get(sprintf('api/v1/accounts/%d/conversations/%d/messages', $accountId, $conversationId))
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot messages response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(int $accountId, int $conversationId, array $attributes = []): array
    {
        $response = $this->request()
            ->post(
                sprintf('api/v1/accounts/%d/conversations/%d/messages', $accountId, $conversationId),
                $attributes,
            )
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot message response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->application->request();
    }
}
