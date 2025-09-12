<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareService
{
    protected string $endpoint;
    protected string $apiToken;
    protected string $accountId;
    protected string $namespaceId;
    protected string $shortenerDomain;

    public function __construct()
    {
        $this->endpoint = rtrim(config('services.cloudflare.endpoint'), '/');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->accountId = config('services.cloudflare.account_id');
        $this->namespaceId = config('services.cloudflare.kv_namespace_id');
        $this->shortenerDomain = config('services.cloudflare.shortener_domain');
    }

    public function put(string $key, string $value): void
    {
        Http::withToken($this->apiToken)
            ->put(
                sprintf(
                    '%s/accounts/%s/storage/kv/namespaces/%s/values/%s',
                    $this->endpoint,
                    $this->accountId,
                    $this->namespaceId,
                    $key
                ),
                $value
            )->throw();
    }

    public function get(string $key): string
    {
        return Http::withToken($this->apiToken)
            ->get(
                sprintf(
                    '%s/accounts/%s/storage/kv/namespaces/%s/values/%s',
                    $this->endpoint,
                    $this->accountId,
                    $this->namespaceId,
                    $key
                )
            )
            ->throw()
            ->body();
    }

    public function delete(string $key): void
    {
        Http::withToken($this->apiToken)
            ->delete(
                sprintf(
                    '%s/accounts/%s/storage/kv/namespaces/%s/values/%s',
                    $this->endpoint,
                    $this->accountId,
                    $this->namespaceId,
                    $key
                )
            )->throw();
    }

    public function shortUrl(string $key): string
    {
        return rtrim($this->shortenerDomain, '/') . '/' . $key;
    }
}

