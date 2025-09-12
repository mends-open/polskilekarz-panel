<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareService
{
    protected string $endpoint;
    protected string $apiToken;
    protected string $accountId;
    protected string $linksNamespaceId;
    protected string $shortenerDomain;

    public function __construct()
    {
        $this->endpoint = rtrim(config('services.cloudflare.endpoint'), '/');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->accountId = config('services.cloudflare.account_id');
        $this->linksNamespaceId = config('services.cloudflare.links_namespace_id');
        $this->shortenerDomain = config('services.cloudflare.shortener_domain');
    }

    protected function kvUrl(string $key): string
    {
        return sprintf(
            '%s/accounts/%s/storage/kv/namespaces/%s/values/%s',
            $this->endpoint,
            $this->accountId,
            $this->linksNamespaceId,
            $key
        );
    }

    public function createLinkIfAbsent(string $key, string $rawUrl): array
    {
        $value = base64_encode($rawUrl);

        $response = Http::withToken($this->apiToken)
            ->withHeaders([
                'Content-Type' => 'text/plain',
                'If-None-Match' => '*',
            ])
            ->send('PUT', $this->kvUrl($key), ['body' => $value]);

        if ($response->successful()) {
            return [
                'success' => true,
                'created' => true,
                'short_url' => $this->shortUrl($key),
                'url' => $rawUrl,
            ];
        }

        if ($response->status() === 412) {
            $existing = $this->getLink($key);

            return [
                'success' => true,
                'created' => false,
                'short_url' => $this->shortUrl($key),
                'url' => $existing,
            ];
        }

        return ['success' => false, 'created' => false];
    }

    public function getLink(string $key): ?string
    {
        $response = Http::withToken($this->apiToken)->get($this->kvUrl($key));

        if ($response->successful()) {
            $decoded = base64_decode($response->body(), true);
            return $decoded === false ? null : $decoded;
        }

        return null;
    }

    public function deleteLink(string $key): bool
    {
        $response = Http::withToken($this->apiToken)->delete($this->kvUrl($key));

        return $response->successful();
    }

    public function shortUrl(string $key): string
    {
        return rtrim($this->shortenerDomain, '/') . '/' . $key;
    }
}
