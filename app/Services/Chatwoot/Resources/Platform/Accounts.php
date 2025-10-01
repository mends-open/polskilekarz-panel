<?php

namespace App\Services\Chatwoot\Resources\Platform;

use App\Services\Chatwoot\Platform;
use App\Services\Chatwoot\Resources\Resource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class Accounts extends Resource
{
    public function __construct(private readonly Platform $platform) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function list(array $query = []): array
    {
        $response = $this->request()
            ->get('platform/api/v1/accounts', $query)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot accounts response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function get(int $accountId): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/accounts/%d', $accountId))
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot account response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->platform->request();
    }
}
