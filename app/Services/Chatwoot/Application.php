<?php

namespace App\Services\Chatwoot;

use App\Services\Chatwoot\Concerns\HandlesResources;
use App\Services\Chatwoot\Resources\Application\Contacts;
use App\Services\Chatwoot\Resources\Application\Conversations;
use App\Services\Chatwoot\Resources\Application\Messages;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class Application extends Service
{
    use HandlesResources;

    protected string $authToken;

    public function __construct(?string $authToken, Factory $http, ?string $endpoint = null)
    {
        parent::__construct($http, $endpoint);

        $this->authToken = $this->resolveAuthToken($authToken);
    }

    public function request(): PendingRequest
    {
        return $this->authorizedRequest($this->authToken);
    }

    protected function resources(): array
    {
        return [
            'messages' => Messages::class,
            'contacts' => Contacts::class,
            'conversations' => Conversations::class,
        ];
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
}
