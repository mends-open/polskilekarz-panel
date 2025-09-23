<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

abstract class Service
{
    protected Factory $http;

    protected string $endpoint;

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    public function __construct(Factory $http, ?string $endpoint = null)
    {
        $this->http = $http;
        $this->config = $this->configuration();
        $this->endpoint = $this->resolveEndpoint($endpoint);
    }

    protected function authorizedRequest(string $accessToken): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'api_access_token' => $accessToken,
            ]);
    }

    protected function resolveEndpoint(?string $endpoint): string
    {
        $endpoint ??= (string) ($this->config['endpoint'] ?? '');

        return rtrim($endpoint, '/');
    }

    /**
     * @return array<string, mixed>
     */
    protected function configuration(): array
    {
        if (! function_exists('config')) {
            return [];
        }

        try {
            $config = config('services.chatwoot', []);
        } catch (Throwable) {
            return [];
        }

        return is_array($config) ? $config : [];
    }
}
