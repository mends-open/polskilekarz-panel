<?php

namespace App\Services\Chatwoot\Resources\Platform;

use App\Services\Chatwoot\Platform;
use App\Services\Chatwoot\Resources\Resource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class AccountUsers extends Resource
{
    public function __construct(private readonly Platform $platform)
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function list(int $accountId, array $query = []): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/accounts/%d/account_users', $accountId), $query)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot account users response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(int $accountId, array $attributes = []): array
    {
        $response = $this->request()
            ->post(sprintf('platform/api/v1/accounts/%d/account_users', $accountId), $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot account user response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->platform->request();
    }
}
