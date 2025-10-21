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
        $keys = $kv->listKeys(['prefix' => $this->entryKeyPrefix($slug)]);

        if ($keys === []) {
            return [[], 0, []];
        }

        $entries = [];
        $metadata = [];
        $aggregate = null;

        foreach ($keys as $key) {
            $name = (string) ($key['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $payload = $kv->retrieve($name);

            if (! is_string($payload) || $payload === '') {
                continue;
            }

            if ($name === $this->entryRecordsKey($slug)) {
                $aggregate = $this->decodeEntryPayload($payload);

                if ($aggregate === null) {
                    Log::warning('Failed to decode Cloudflare link entries payload', [
                        'slug' => $slug,
                        'key' => $name,
                    ]);
                }

                continue;
            }

            $entry = $this->decodeEntryRecord($payload);

            if ($entry === null) {
                Log::debug('Ignoring Cloudflare link entry payload', [
                    'slug' => $slug,
                    'key' => $name,
                ]);

                continue;
            }

            $entry['key'] ??= $name;

            $entries[] = $entry;

            $this->mergeMetadataFromEntry($entry, $metadata);
        }

        if ($aggregate !== null) {
            $metadata = array_replace($metadata, $this->extractEntryMetadata($aggregate));

            $aggregateEntries = $this->normalizeEntryRecords($aggregate['entries'] ?? []);

            if ($entries === []) {
                $entries = $aggregateEntries;

                foreach ($entries as &$entry) {
                    $entry['key'] ??= $this->entryRecordsKey($slug);
                }

                unset($entry);
            } else {
                foreach ($aggregateEntries as $record) {
                    $entries = $this->mergeEntryPayload($entries, $record);
                }
            }

            $total = $this->resolveEntryTotal($aggregate, $entries);
        } else {
            $total = count($entries);
        }

        $this->sortEntryRecords($entries);

        return [$entries, $total, $metadata];
    }

    protected function entryRecordsKey(string $slug): string
    {
        return $slug.':entries';
    }

    protected function entryKeyPrefix(string $slug): string
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

    protected function resolveShortLink(string $slug): ?string
    {
        if ($slug === '' || $this->domain === '') {
            return null;
        }

        return $this->buildShortLink($slug);
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
     * @return array<string, mixed>|null
     */
    protected function decodeEntryPayload(string $payload): ?array
    {
        foreach ($this->payloadCandidates($payload) as $candidate) {
            $decoded = json_decode($candidate, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return iterable<string>
     */
    protected function payloadCandidates(string $payload): iterable
    {
        $yielded = [$payload];

        $base64 = base64_decode($payload, true);

        if (is_string($base64) && $base64 !== '') {
            $yielded[] = $base64;
        }

        foreach ($yielded as $candidate) {
            yield $candidate;

            if ($this->isGzipPayload($candidate)) {
                $decoded = gzdecode($candidate);

                if (is_string($decoded) && $decoded !== '') {
                    yield $decoded;
                }
            }
        }
    }

    protected function isGzipPayload(string $payload): bool
    {
        return strlen($payload) >= 2 && str_starts_with($payload, "\x1F\x8B");
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeEntryRecord(string $payload): ?array
    {
        $decoded = $this->decodeEntryPayload($payload);

        if ($decoded === null) {
            return null;
        }

        if (isset($decoded['entry']) && is_array($decoded['entry'])) {
            $decoded = $decoded['entry'];
        }

        if (isset($decoded['entries']) && is_array($decoded['entries'])) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    protected function mergeMetadataFromEntry(array $entry, array &$metadata): void
    {
        foreach (['slug', 'url', 'short_url'] as $key) {
            if (! array_key_exists($key, $metadata) && isset($entry[$key]) && is_string($entry[$key]) && $entry[$key] !== '') {
                $metadata[$key] = $entry[$key];
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    protected function mergeEntryPayload(array $entries, array $payload): array
    {
        $identifier = $payload['identifier'] ?? null;

        if (is_string($identifier) && $identifier !== '') {
            foreach ($entries as $index => $entry) {
                if (($entry['identifier'] ?? null) === $identifier) {
                    $entries[$index] = $this->mergeEntryAttributes($entry, $payload);

                    return $entries;
                }
            }
        }

        $entries[] = $payload;

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function mergeEntryAttributes(array $base, array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (! array_key_exists($key, $base)) {
                $base[$key] = $value;

                continue;
            }

            if (is_array($value) && is_array($base[$key])) {
                $base[$key] = array_replace_recursive($base[$key], $value);
            } elseif ($base[$key] === null) {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    protected function sortEntryRecords(array &$entries): void
    {
        usort($entries, function (array $left, array $right): int {
            $leftTimestamp = (string) ($left['timestamp'] ?? '');
            $rightTimestamp = (string) ($right['timestamp'] ?? '');

            if ($leftTimestamp === $rightTimestamp) {
                return strcmp((string) ($left['identifier'] ?? ''), (string) ($right['identifier'] ?? ''));
            }

            return strcmp($leftTimestamp, $rightTimestamp);
        });
    }
}
