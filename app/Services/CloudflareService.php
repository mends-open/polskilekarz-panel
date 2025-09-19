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
    protected int $slugLength;

    public function __construct()
    {
        $cfg = config('services.cloudflare');
        $this->endpoint = rtrim($cfg['endpoint'], '/');
        $this->token = $cfg['api_token'];
        $this->account = $cfg['account_id'];
        $linkCfg = $cfg['links'];
        $this->namespace = $linkCfg['namespace_id'];
        $this->domain = $linkCfg['domain'];
        $this->slugLength = (int) $linkCfg['slug_length'];
    }

    protected function kvUrl(string $slug): string
    {
        return sprintf(
            '%s/accounts/%s/storage/kv/namespaces/%s/values/%s',
            $this->endpoint,
            $this->account,
            $this->namespace,
            $slug
        );
    }

    /**
     * Create a short link.
     */
    public function shorten(string $rawUrl): array
    {
        $slug = $this->generateSlug();

        if ($this->storeIfAbsent($slug, $rawUrl) !== true) {
            return ['success' => false, 'created' => false];
        }

        $link = CloudflareLink::create([
            'slug' => $slug,
            'url' => $rawUrl,
        ]);

        Log::info('Created Cloudflare short link', [
            'slug' => $slug,
            'url' => $rawUrl,
            'link_id' => $link->id,
        ]);

        return [
            'success' => true,
            'created' => true,
            'short_url' => $this->buildShortUrl($slug),
            'url' => $rawUrl,
            'link' => $link,
        ];
    }

    protected function generateSlug(): string
    {
        return Str::random($this->slugLength);
    }

    protected function storeIfAbsent(string $slug, string $rawUrl): ?bool
    {
        $response = Http::withToken($this->token)
            ->withHeaders([
                'Content-Type' => 'text/plain',
                'If-None-Match' => '*',
            ])
            ->send('PUT', $this->kvUrl($slug), ['body' => base64_encode($rawUrl)]);

        if ($response->status() === 412) {
            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::error('Failed to store Cloudflare short link', [
            'slug' => $slug,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    protected function buildShortUrl(string $slug): string
    {
        return rtrim($this->domain, '/') . '/' . $slug;
    }
}
