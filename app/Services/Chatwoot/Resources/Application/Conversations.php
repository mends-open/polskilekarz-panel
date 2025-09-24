<?php

namespace App\Services\Chatwoot\Resources\Application;

use App\Services\Chatwoot\Application;
use App\Services\Chatwoot\Resources\Resource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class Conversations extends Resource
{
    public function __construct(private readonly Application $application)
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function list(int $accountId, array $query = []): array
    {
        $response = $this->request()
            ->get(sprintf('api/v1/accounts/%d/conversations', $accountId), $query)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot conversations response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function get(int $accountId, int $conversationId): array
    {
        $response = $this->request()
            ->get(sprintf('api/v1/accounts/%d/conversations/%d', $accountId, $conversationId))
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot conversation response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(int $accountId, array $attributes): array
    {
        $response = $this->request()
            ->post(sprintf('api/v1/accounts/%d/conversations', $accountId), $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot conversation creation response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function update(int $accountId, int $conversationId, array $attributes): array
    {
        $response = $this->request()
            ->patch(sprintf('api/v1/accounts/%d/conversations/%d', $accountId, $conversationId), $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot conversation update response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->application->request();
    }
}
