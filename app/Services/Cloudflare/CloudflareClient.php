<?php

namespace App\Services\Cloudflare;

use App\Services\Cloudflare\Storage\KVNamespace;
use Illuminate\Http\Client\Factory;
use Throwable;

class CloudflareClient
{
    protected Factory $http;

    protected string $endpoint;

    protected string $token;

    protected string $accountId;

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    public function __construct(Factory $http, ?string $endpoint = null, ?string $token = null, ?string $accountId = null)
    {
        $this->http = $http;
        $this->config = $this->configuration();

        $this->endpoint = $this->resolveEndpoint($endpoint);
        $this->token = $token ?? (string) ($this->config['api_token'] ?? '');
        $this->accountId = $accountId ?? (string) ($this->config['account_id'] ?? '');
    }

    public function kv(string $namespaceId, array $options = []): KVNamespace
    {
        $domain = $options['domain'] ?? null;

        return new KVNamespace(
            $this->http,
            $this->endpoint,
            $this->token,
            $this->accountId,
            $namespaceId,
            is_string($domain) ? $domain : null,
        );
    }

    public function links(): LinkShortener
    {
        return new LinkShortener($this);
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
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
            $config = config('services.cloudflare', []);
        } catch (Throwable) {
            return [];
        }

        return is_array($config) ? $config : [];
    }

    protected function resolveEndpoint(?string $endpoint): string
    {
        $endpoint ??= (string) ($this->config['endpoint'] ?? '');

        return rtrim($endpoint, '/');
    }
}
