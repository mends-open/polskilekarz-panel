<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use RuntimeException;

class ChatwootClient extends Service
{
    public function __construct(Factory $http, ?string $endpoint = null)
    {
        parent::__construct($http, $endpoint);
    }

    public function platform(?string $accessToken = null): Platform
    {
        return new Platform($this->http, $this->endpoint, $accessToken);
    }

    public function application(?string $accessToken = null): Application
    {
        return new Application($accessToken, $this->http, $this->endpoint);
    }

    public function impersonateFallback(?int $accountId = null): Application
    {
        $userId = $this->fallbackUserId();

        return $this->platform()->impersonate($userId, $accountId);
    }

    private function fallbackUserId(): int
    {
        $configured = $this->config['fallback_user_id'] ?? null;

        if (is_int($configured) && $configured > 0) {
            return $configured;
        }

        if (is_string($configured) && ctype_digit($configured) && (int) $configured > 0) {
            return (int) $configured;
        }

        throw new RuntimeException('Chatwoot fallback user id is not configured.');
    }
}
