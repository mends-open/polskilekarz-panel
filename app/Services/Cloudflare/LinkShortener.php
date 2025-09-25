<?php

namespace App\Services\Cloudflare;

use App\Models\CloudflareLink;
use App\Services\Cloudflare\Storage\KVNamespace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;

class LinkShortener
{
    protected string $namespace;

    protected string $domain;

    protected int $slugLength;

    protected string $logsNamespace;

    public function __construct(protected CloudflareClient $cloudflare)
    {
        $links = $this->cloudflare->config('links', []);
        $links = is_array($links) ? $links : [];

        $this->namespace = (string) ($links['namespace_id'] ?? '');
        $this->domain = (string) ($links['domain'] ?? '');
        $this->slugLength = (int) ($links['slug_length'] ?? 8);
        $this->logsNamespace = (string) ($links['logs_namespace_id'] ?? '');
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

    public function entry(string $slug, ?string $url = null): array
    {
        $slug = trim($slug);

        $entries = [];
        $counter = 0;

        if ($slug !== '' && $this->logsNamespace !== '') {
            $kv = $this->cloudflare->kv($this->logsNamespace);

            $entries = $this->mergeEntryLogs($kv, $slug);
            $counter = $this->extractCounter($kv->retrieve($slug . ':counter'));
        }

        return [
            'slug' => $slug,
            'url' => $url,
            'short_url' => $this->buildShortLinkIfConfigured($slug),
            'total' => $counter,
            'entries' => $entries,
        ];
    }

    public function logs(CloudflareLink $link): array
    {
        return $this->entry($link->slug, $link->url);
    }

    protected function generateSlug(): string
    {
        return Str::random($this->slugLength);
    }

    protected function mergeEntryLogs(KVNamespace $kv, string $slug): array
    {
        $keys = $kv->listKeys(['prefix' => $slug . ':']);

        $entries = [];

        foreach ($keys as $key) {
            $name = (string) ($key['name'] ?? '');

            if ($name === '' || $name === $slug . ':counter') {
                continue;
            }

            $payload = $kv->retrieve($name);

            if (! is_string($payload) || $payload === '') {
                continue;
            }

            $decoded = $this->decodeEntryPayload($payload, $slug, $name);

            if ($decoded === null) {
                continue;
            }

            $entries[] = array_merge([
                'key' => $name,
                'index' => $this->extractIndex($slug, $name),
            ], $decoded);
        }

        usort($entries, function (array $left, array $right): int {
            $leftIndex = $left['index'] ?? PHP_INT_MAX;
            $rightIndex = $right['index'] ?? PHP_INT_MAX;

            if ($leftIndex === $rightIndex) {
                return strcmp((string) ($left['timestamp'] ?? ''), (string) ($right['timestamp'] ?? ''));
            }

            return $leftIndex <=> $rightIndex;
        });

        return array_values($entries);
    }

    protected function decodeEntryPayload(string $payload, string $slug, string $key): ?array
    {
        $decoded = gzdecode($payload);

        if ($decoded === false) {
            Log::warning('Failed to decompress Cloudflare link log payload', [
                'slug' => $slug,
                'key' => $key,
            ]);

            return null;
        }

        try {
            $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('Failed to decode Cloudflare link log payload', [
                'slug' => $slug,
                'key' => $key,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        return is_array($data) ? $data : null;
    }

    protected function extractIndex(string $slug, string $name): ?int
    {
        $prefix = $slug . ':';

        if (! str_starts_with($name, $prefix)) {
            return null;
        }

        $value = substr($name, strlen($prefix));

        return is_numeric($value) ? (int) $value : null;
    }

    protected function extractCounter(?string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    protected function buildShortLinkIfConfigured(string $slug): ?string
    {
        if ($slug === '' || $this->domain === '') {
            return null;
        }

        return $this->buildShortLink($slug);
    }
}
