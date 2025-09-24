<?php

namespace App\Services\Chatwoot\Resources\Application;

use App\Services\Chatwoot\Application;
use App\Services\Chatwoot\Resources\Resource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class Contacts extends Resource
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
            ->get(sprintf('api/v1/accounts/%d/contacts', $accountId), $query)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot contacts response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function get(int $accountId, int $contactId): array
    {
        $response = $this->request()
            ->get(sprintf('api/v1/accounts/%d/contacts/%d', $accountId, $contactId))
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot contact response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(int $accountId, array $attributes): array
    {
        $response = $this->request()
            ->post(sprintf('api/v1/accounts/%d/contacts', $accountId), $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot contact creation response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function update(int $accountId, int $contactId, array $attributes): array
    {
        $response = $this->request()
            ->patch(sprintf('api/v1/accounts/%d/contacts/%d', $accountId, $contactId), $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot contact update response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->application->request();
    }
}
