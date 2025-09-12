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
        $linkCfg = $cfg['link_shortener'];
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
     * Create a short link or return existing entry.
     */
    public function create(string $rawUrl, array $attributes): array
    {
        if ($existing = CloudflareLink::where('value', $rawUrl)->first()) {
            Log::info('Using existing Cloudflare short link', [
                'key' => $existing->key,
                'url' => $rawUrl,
                'link_id' => $existing->id,
            ]);

            return [
                'success' => true,
                'created' => false,
                'short_url' => $this->shortUrl($existing->key),
                'url' => $rawUrl,
                'link' => $existing,
            ];
        }

        $value = base64_encode($rawUrl);

        $attempts = 0;

        do {
            $key = Str::random($this->keyLength);

            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Content-Type' => 'text/plain',
                    'If-None-Match' => '*',
                ])
                ->send('PUT', $this->kvUrl($key), ['body' => $value]);

            if ($response->status() === 412) {
                $attempts++;
                continue;
            }

            if ($response->successful()) {
                $link = CloudflareLink::create(array_merge($attributes, [
                    'key' => $key,
                    'value' => $rawUrl,
                ]));

                Log::info('Created Cloudflare short link', [
                    'key' => $key,
                    'url' => $rawUrl,
                    'link_id' => $link->id,
                ]);

                return [
                    'success' => true,
                    'created' => true,
                    'short_url' => $this->shortUrl($key),
                    'url' => $rawUrl,
                    'link' => $link,
                ];
            }

            Log::error('Failed to create Cloudflare short link', [
                'key' => $key,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'created' => false];
        } while ($attempts < 3);

        Log::error('Failed to generate unique key for Cloudflare short link', [
            'url' => $rawUrl,
            'attempts' => $attempts,
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
