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

        if (is_string($payload) && $payload !== '') {
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
        }

        return $this->collectIndividualEntryRecords($kv, $slug);
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
        return Arr::except($decoded, ['entries', 'entry', 'total']);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: array<string, mixed>}
     */
    protected function collectIndividualEntryRecords(KVNamespace $kv, string $slug): array
    {
        $keys = $this->listEntryRecordKeys($kv, $slug);

        if ($keys === []) {
            return [[], 0, []];
        }

        $entries = [];
        $metadata = [];

        foreach ($keys as $key) {
            $payload = $kv->retrieve($key);

            if (! is_string($payload) || $payload === '') {
                continue;
            }

            foreach ($this->payloadCandidates($payload) as $candidate) {
                [$decoded, $valid] = $this->tryDecodeJson($candidate);

                if (! $valid || ! is_array($decoded)) {
                    continue;
                }

                $metadata = array_merge($metadata, $this->extractEntryMetadata($decoded));

                $entry = $this->resolveIndividualEntry($decoded);

                if ($entry !== null) {
                    $entries[] = $entry;
                }

                break;
            }
        }

        if ($entries === []) {
            return [[], 0, $metadata];
        }

        $entries = $this->sortEntryRecords($entries);

        return [$entries, count($entries), $metadata];
    }

    /**
     * @return array<int, string>
     */
    protected function listEntryRecordKeys(KVNamespace $kv, string $slug): array
    {
        $result = $kv->listKeys([
            'prefix' => $this->entryRecordsKey($slug).':',
            'limit' => 1000,
        ]);

        if (! is_array($result) || $result === []) {
            return [];
        }

        $keys = array_map(function ($item) {
            if (is_array($item) && isset($item['name']) && is_string($item['name'])) {
                return $item['name'];
            }

            if (is_string($item)) {
                return $item;
            }

            return null;
        }, $result);

        $keys = array_filter($keys);

        sort($keys);

        return array_values($keys);
    }

    protected function resolveIndividualEntry(array $decoded): ?array
    {
        if (isset($decoded['entry']) && is_array($decoded['entry'])) {
            return $this->normalizeSingleEntry($decoded['entry']);
        }

        if (isset($decoded['entries']) && is_array($decoded['entries'])) {
            $entries = $this->normalizeEntryRecords($decoded['entries']);

            return $entries[0] ?? null;
        }

        $candidate = Arr::except($decoded, ['slug', 'url', 'short_url', 'total']);

        return $this->normalizeSingleEntry($candidate);
    }

    protected function normalizeSingleEntry(array $entry): ?array
    {
        $normalized = $this->normalizeEntryRecords([$entry]);

        return $normalized[0] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    protected function sortEntryRecords(array $entries): array
    {
        usort($entries, function (array $left, array $right): int {
            $leftTimestamp = isset($left['timestamp']) && is_string($left['timestamp'])
                ? strtotime($left['timestamp'])
                : null;
            $rightTimestamp = isset($right['timestamp']) && is_string($right['timestamp'])
                ? strtotime($right['timestamp'])
                : null;

            if ($leftTimestamp !== null && $rightTimestamp !== null) {
                if ($leftTimestamp === $rightTimestamp) {
                    return $this->compareIdentifiers($left, $right);
                }

                return $rightTimestamp <=> $leftTimestamp;
            }

            if ($leftTimestamp !== null) {
                return -1;
            }

            if ($rightTimestamp !== null) {
                return 1;
            }

            return $this->compareIdentifiers($left, $right);
        });

        return $entries;
    }

    protected function compareIdentifiers(array $left, array $right): int
    {
        $leftIdentifier = isset($left['identifier']) && is_string($left['identifier'])
            ? $left['identifier']
            : null;
        $rightIdentifier = isset($right['identifier']) && is_string($right['identifier'])
            ? $right['identifier']
            : null;

        if ($leftIdentifier !== null && $rightIdentifier !== null) {
            return $leftIdentifier <=> $rightIdentifier;
        }

        if ($leftIdentifier !== null) {
            return -1;
        }

        if ($rightIdentifier !== null) {
            return 1;
        }

        return 0;
    }
}
