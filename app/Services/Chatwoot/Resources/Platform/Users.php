<?php

namespace App\Services\Chatwoot\Resources\Platform;

use App\Services\Chatwoot\Platform;
use App\Services\Chatwoot\Resources\Resource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class Users extends Resource
{
    public function __construct(private readonly Platform $platform)
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function list(array $query = []): array
    {
        $response = $this->request()
            ->get('platform/api/v1/users', $query)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot users response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function get(int $userId, ?int $accountId = null): array
    {
        $response = $this->request()
            ->get(sprintf('platform/api/v1/users/%d', $userId))
            ->throw();

        $user = $this->decodeResponse($response, 'Chatwoot user response was not valid JSON.');

        if ($accountId !== null && ! $this->platform->userBelongsToAccount($user, $accountId)) {
            throw new RuntimeException('Chatwoot user does not belong to the specified account.');
        }

        return $user;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(array $attributes): array
    {
        $response = $this->request()
            ->post('platform/api/v1/users', $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot user creation response was not valid JSON.');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function update(int $userId, array $attributes): array
    {
        $response = $this->request()
            ->patch(sprintf('platform/api/v1/users/%d', $userId), $attributes)
            ->throw();

        return $this->decodeResponse($response, 'Chatwoot user update response was not valid JSON.');
    }

    protected function request(): PendingRequest
    {
        return $this->platform->request();
    }
}
