<?php

namespace App\Services;

use App\Exceptions\CloudflareLinkExistsException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected string $endpoint;
    protected string $token;
    protected string $account;
    protected string $namespace;
    protected string $domain;

    public function __construct()
    {
        $cfg = config('services.cloudflare');
        $this->endpoint = rtrim($cfg['endpoint'], '/');
        $this->token = $cfg['api_token'];
        $this->account = $cfg['account_id'];
        $linkCfg = $cfg['link_shortener'];
        $this->namespace = $linkCfg['namespace_id'];
        $this->domain = $linkCfg['domain'];
    }

    protected function kvUrl(string $key): string
    {
        return sprintf(
            '%s/accounts/%s/storage/kv/namespaces/%s/values/%s',
            $this->endpoint,
            $this->account,
            $this->namespace,
            $key
        );
    }

    /**
     * Create a short link if it does not already exist.
     *
     * @throws CloudflareLinkExistsException
     */
    public function create(string $key, string $rawUrl): array
    {
        if ($existing = $this->get($key)) {
            Log::warning('Attempted to overwrite existing Cloudflare short link', ['key' => $key, 'url' => $existing]);

            throw new CloudflareLinkExistsException($key, $existing);
        }

        $value = base64_encode($rawUrl);

        $response = Http::withToken($this->token)
            ->withHeaders([
                'Content-Type' => 'text/plain',
            ])
            ->send('PUT', $this->kvUrl($key), ['body' => $value]);

        if ($response->successful()) {
            Log::info('Created Cloudflare short link', ['key' => $key, 'url' => $rawUrl]);

            return [
                'success' => true,
                'created' => true,
                'short_url' => $this->shortUrl($key),
                'url' => $rawUrl,
            ];
        }

        Log::error('Failed to create Cloudflare short link', [
            'key' => $key,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return ['success' => false, 'created' => false];
    }

    public function get(string $key): ?string
    {
        $response = Http::withToken($this->token)->get($this->kvUrl($key));

        if ($response->successful()) {
            $decoded = base64_decode($response->body(), true);
            return $decoded === false ? null : $decoded;
        }

        return null;
    }

    public function delete(string $key): bool
    {
        $response = Http::withToken($this->token)->delete($this->kvUrl($key));

        return $response->successful();
    }

    public function shortUrl(string $key): string
    {
        return rtrim($this->domain, '/') . '/' . $key;
    }
}
