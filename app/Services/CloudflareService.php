<?php

namespace App\Services;

use App\Models\CloudflareLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudflareService
{
    protected string $endpoint;
    protected string $token;
    protected string $account;
    protected string $namespace;
    protected string $domain;
    protected int $keyLength;

    public function __construct()
    {
        $cfg = config('services.cloudflare');
        $this->endpoint = rtrim($cfg['endpoint'], '/');
        $this->token = $cfg['api_token'];
        $this->account = $cfg['account_id'];
        $linkCfg = $cfg['links'];
        $this->namespace = $linkCfg['namespace_id'];
        $this->domain = $linkCfg['domain'];
        $this->keyLength = (int) $linkCfg['key_length'];
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
     * Create a short link.
     */
    public function shorten(string $rawUrl): array
    {
        $key = $this->generateKey();

        if ($this->storeIfAbsent($key, $rawUrl) !== true) {
            return ['success' => false, 'created' => false];
        }

        $link = CloudflareLink::create([
            'key' => $key,
            'value' => $rawUrl,
        ]);

        Log::info('Created Cloudflare short link', [
            'key' => $key,
            'url' => $rawUrl,
            'link_id' => $link->id,
        ]);

        return [
            'success' => true,
            'created' => true,
            'short_url' => $this->buildShortUrl($key),
            'url' => $rawUrl,
            'link' => $link,
        ];
    }

    protected function generateKey(): string
    {
        return Str::random($this->keyLength);
    }

    protected function storeIfAbsent(string $key, string $rawUrl): ?bool
    {
        $response = Http::withToken($this->token)
            ->withHeaders([
                'Content-Type' => 'text/plain',
                'If-None-Match' => '*',
            ])
            ->send('PUT', $this->kvUrl($key), ['body' => base64_encode($rawUrl)]);

        if ($response->status() === 412) {
            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::error('Failed to store Cloudflare short link', [
            'key' => $key,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    protected function buildShortUrl(string $key): string
    {
        return rtrim($this->domain, '/') . '/' . $key;
    }
}
