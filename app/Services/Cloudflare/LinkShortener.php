<?php

namespace App\Services\Cloudflare;

use App\Models\CloudflareLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkShortener
{
    protected string $namespace;

    protected string $domain;

    protected int $slugLength;

    public function __construct(protected CloudflareClient $cloudflare)
    {
        $links = $this->cloudflare->config('links', []);
        $links = is_array($links) ? $links : [];

        $this->namespace = (string) ($links['namespace_id'] ?? '');
        $this->domain = (string) ($links['domain'] ?? '');
        $this->slugLength = (int) ($links['slug_length'] ?? 8);
    }

    /**
     * Create a short link.
     */
    public function shorten(string $rawUrl): array
    {
        $slug = $this->generateSlug();

        $kv = $this->cloudflare->kv($this->namespace, ['domain' => $this->domain]);
        $result = $kv->createIfAbsent($slug, $rawUrl);

        if ($result->conflicted()) {
            return ['success' => false, 'created' => false];
        }

        if ($result->failed()) {
            $response = $result->response();

            Log::error('Failed to store Cloudflare short link', [
                'slug' => $slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

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
            'created' => $result->created(),
            'short_url' => $result->buildShortLink(),
            'url' => $rawUrl,
            'link' => $link,
        ];
    }

    public function buildShortLink(string $slug): string
    {
        return rtrim($this->domain, '/') . '/' . $slug;
    }

    protected function generateSlug(): string
    {
        return Str::random($this->slugLength);
    }
}
