<?php

namespace App\Services\Cloudflare;

use App\Models\CloudflareLink;
use App\Services\Cloudflare\Storage\KVNamespace;
use App\Support\Metadata\Metadata;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkShortener
{
    protected string $namespace;

    protected string $domain;

    protected int $slugLength;

    public function __construct(protected CloudflareClient $cloudflare)
    {
        $config = $this->cloudflare->config('shortener', []);
        $config = is_array($config) ? $config : [];

        $this->namespace = (string) ($config['links_namespace_id'] ?? '');
        $this->domain = (string) ($config['domain'] ?? '');
        $this->slugLength = (int) ($config['slug_length'] ?? 8);
    }

    /**
     * Create a short link.
     */
    public function shorten(string $rawUrl, array $metadata = []): string
    {
        $slug = $this->generateSlug();

        $kv = $this->cloudflare->kv($this->namespace, ['domain' => $this->domain]);
        $metadataArray = Metadata::prepare($metadata);
        $encodedUrl = $this->encodeUrl($rawUrl);

        $result = $kv->createIfAbsent($slug, $encodedUrl, $metadataArray);

        if ($result->conflicted()) {
            throw new \RuntimeException('Short link already exists');
        }

        if ($result->failed()) {
            $response = $result->response();

            Log::error('Failed to store Cloudflare short link', [
                'slug' => $slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Failed to store Cloudflare short link');
        }

        $link = CloudflareLink::create([
            'slug' => $slug,
            'url' => $rawUrl,
            'metadata' => $metadataArray,
        ]);

        Log::info('Created Cloudflare short link', [
            'slug' => $slug,
            'url' => $rawUrl,
            'link_id' => $link->id,
            'metadata' => $metadataArray,
        ]);

        return $result->buildShortLink();
    }

    public function buildShortLink(string $slug): string
    {
        return rtrim($this->domain, '/').'/'.$slug;
    }

    protected function encodeUrl(string $url): string
    {
        return base64_encode($url);
    }

    public function entries(string $slug, ?string $url = null): array
    {
        $slug = trim($slug);

        $result = [
            'slug' => $slug,
            'url' => $url,
            'short_url' => $this->resolveShortLink($slug),
            'total' => 0,
            'entries' => [],
        ];

        if ($slug === '' || $this->namespace === '') {
            return $result;
        }

        $kv = $this->cloudflare->kv($this->namespace, ['domain' => $this->domain]);
        [$entries, $counter, $metadata] = $this->collectEntryRecords($kv, $slug);

        $result['entries'] = $entries;
        $result['total'] = $counter;

        if ($metadata !== []) {
            foreach (['slug', 'url', 'short_url'] as $key) {
                if (array_key_exists($key, $metadata)) {
                    $result[$key] = $metadata[$key];
                }
            }
        }

        return $result;
    }

    public function entriesFor(CloudflareLink $link): array
    {
        return $this->entries($link->slug, $link->url);
    }

    public function logs(CloudflareLink $link): array
    {
        return $this->entriesFor($link);
    }

    protected function generateSlug(): string
    {
        return Str::random($this->slugLength);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: array<string, mixed>}
     */
    protected function collectEntryRecords(KVNamespace $kv, string $slug): array
    {
        $payload = $kv->retrieve($this->entryRecordsKey($slug));

        if (! is_string($payload) || $payload === '') {
            return [[], 0, []];
        }

        foreach ($this->payloadCandidates($payload) as $candidate) {
            [$decoded, $valid] = $this->tryDecodeJson($candidate);

            if (! $valid || ! is_array($decoded)) {
                continue;
            }

            $entries = $this->normalizeEntryRecords($decoded['entries'] ?? []);
            $total = $this->resolveEntryTotal($decoded, $entries);
            $metadata = $this->extractEntryMetadata($decoded);

            return [$entries, $total, $metadata];
        }

        Log::warning('Failed to decode Cloudflare link entries payload', [
            'slug' => $slug,
        ]);

        return [[], 0, []];
    }

    protected function entryRecordsKey(string $slug): string
    {
        return $slug.':entries';
    }

    /**
     * @param  array<int, mixed>  $entries
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeEntryRecords(array $entries): array
    {
        return array_values(array_filter(array_map(function ($entry) {
            return is_array($entry) ? $entry : null;
        }, $entries)));
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @param  array<int, array<string, mixed>>  $entries
     */
    protected function resolveEntryTotal(array $decoded, array $entries): int
    {
        $total = $decoded['total'] ?? null;

        if (is_int($total)) {
            return $total;
        }

        if (is_string($total) && ctype_digit($total)) {
            return (int) $total;
        }

        return count($entries);
    }

    /**
     * @return iterable<string>
     */
    protected function payloadCandidates(string $payload): iterable
    {
        $candidates = [$payload];

        $base64 = base64_decode($payload, true);

        if (is_string($base64) && $base64 !== '') {
            $candidates[] = $base64;
        }

        foreach ($candidates as $candidate) {
            yield $candidate;

            if ($this->isGzipPayload($candidate)) {
                $decoded = gzdecode($candidate);

                if ($decoded !== false) {
                    yield $decoded;
                }
            }
        }
    }

    protected function isGzipPayload(string $payload): bool
    {
        return strlen($payload) >= 2 && str_starts_with($payload, "\x1F\x8B");
    }

    protected function resolveShortLink(string $slug): ?string
    {
        if ($slug === '' || $this->domain === '') {
            return null;
        }

        return $this->buildShortLink($slug);
    }

    /**
     * @return array{0: mixed, 1: bool}
     */
    protected function tryDecodeJson(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return [$decoded, true];
        }

        return [null, false];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    protected function extractEntryMetadata(array $decoded): array
    {
        return Arr::except($decoded, ['entries', 'total']);
    }
}
