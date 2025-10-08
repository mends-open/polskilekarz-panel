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

    protected string $entriesNamespace;

    public function __construct(protected CloudflareClient $cloudflare)
    {
        $config = $this->cloudflare->config('shortener', []);
        $config = is_array($config) ? $config : [];

        $this->namespace = (string) ($config['links_namespace_id'] ?? '');
        $this->domain = (string) ($config['domain'] ?? '');
        $this->slugLength = (int) ($config['slug_length'] ?? 8);
        $this->entriesNamespace = (string) ($config['entries_namespace_id'] ?? '');
    }

    /**
     * Create a short link.
     */
    public function shorten(string $rawUrl, array $metadata = []): string
    {
        $slug = $this->generateSlug();

        $kv = $this->cloudflare->kv($this->namespace, ['domain' => $this->domain]);
        $sanitisedMetadata = $this->sanitiseMetadata($metadata);
        $payload = [
            'url' => $rawUrl,
            'metadata' => $sanitisedMetadata,
        ];

        $encodedPayload = $this->encodePayload($payload, $rawUrl);

        $result = $kv->createIfAbsent($slug, $encodedPayload, $sanitisedMetadata);

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
            'metadata' => $sanitisedMetadata,
        ]);

        Log::info('Created Cloudflare short link', [
            'slug' => $slug,
            'url' => $rawUrl,
            'link_id' => $link->id,
            'metadata' => $sanitisedMetadata,
        ]);

        return $result->buildShortLink();
    }

    public function buildShortLink(string $slug): string
    {
        return rtrim($this->domain, '/').'/'.$slug;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, string>
     */
    protected function sanitiseMetadata(array $metadata): array
    {
        return collect($metadata)
            ->map(fn ($value): ?string => is_scalar($value) ? (string) $value : null)
            ->filter(fn (?string $value): bool => $value !== null && $value !== '')
            ->all();
    }

    /**
     * @param array{url: string, metadata: array<string, string>} $payload
     */
    protected function encodePayload(array $payload, string $fallback): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::error('Failed to encode Cloudflare short link payload', [
                'payload' => $payload,
                'exception' => $exception->getMessage(),
            ]);

        }

        return $fallback;
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

        if ($slug === '' || $this->entriesNamespace === '') {
            return $result;
        }

        $kv = $this->cloudflare->kv($this->entriesNamespace);
        [$entries, $counter] = $this->collectEntryRecords($kv, $slug);

        $result['entries'] = $entries;
        $result['total'] = $counter;

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
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    protected function collectEntryRecords(KVNamespace $kv, string $slug): array
    {
        $keys = $kv->listKeys(['prefix' => $slug.':']);

        if ($keys === []) {
            return [[], 0];
        }

        $entries = [];
        $counter = 0;
        $counterKey = $this->entryCounterKey($slug);

        foreach ($keys as $key) {
            $name = (string) ($key['name'] ?? '');

            if ($name === '') {
                continue;
            }

            if ($name === $counterKey) {
                $counter = $this->parseCounter($kv->retrieve($name));

                continue;
            }

            $segments = $this->extractEntrySegments($slug, $name);

            if ($segments === null) {
                continue;
            }

            [$index, $path] = $segments;

            $payload = $kv->retrieve($name);

            if (! is_string($payload) || $payload === '') {
                continue;
            }

            $value = $this->decodeEntryValue($payload, $slug, $name, $path !== []);

            if ($value === null && $path === []) {
                continue;
            }

            $entry = &$entries[$index];

            if (! isset($entry)) {
                $entry = [
                    'index' => $index,
                    'keys' => [],
                ];
            }

            if (! in_array($name, $entry['keys'], true)) {
                $entry['keys'][] = $name;
            }

            $entry['key'] ??= $name;

            if ($path === []) {
                if (is_array($value)) {
                    $this->mergeEntryPayload($entry, $value);
                } elseif ($value !== null) {
                    $entry['payload'] = $value;
                }

                continue;
            }

            $this->assignEntryValue($entry, $path, $value);
        }

        if ($entries !== []) {
            foreach ($entries as &$entry) {
                $entry['keys'] = array_values(array_unique($entry['keys']));
            }

            unset($entry);
        }

        $this->sortEntryRecords($entries);

        return [array_values($entries), $counter];
    }

    /**
     * @param  array<int, string>  $path
     */
    protected function assignEntryValue(array &$entry, array $path, mixed $value): void
    {
        if ($path === []) {
            return;
        }

        Arr::set($entry, implode('.', $path), $value);
    }

    protected function mergeEntryPayload(array &$entry, array $payload): void
    {
        foreach ($payload as $key => $value) {
            if ($key === 'index' || $key === 'keys') {
                continue;
            }

            if ($key === 'key') {
                $entry['key'] = $entry['key'] ?? (is_string($value) ? $value : null);

                continue;
            }

            $entry[$key] = $value;
        }
    }

    protected function parseCounter(?string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    protected function entryCounterKey(string $slug): string
    {
        return $slug.':counter';
    }

    protected function sortEntryRecords(array &$entries): void
    {
        usort($entries, function (array $left, array $right): int {
            $leftIndex = $left['index'] ?? PHP_INT_MAX;
            $rightIndex = $right['index'] ?? PHP_INT_MAX;

            if ($leftIndex === $rightIndex) {
                return strcmp((string) ($left['timestamp'] ?? ''), (string) ($right['timestamp'] ?? ''));
            }

            return $leftIndex <=> $rightIndex;
        });
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

    /**
     * @return array{0: int, 1: array<int, string>}|null
     */
    protected function extractEntrySegments(string $slug, string $name): ?array
    {
        $prefix = $slug.':';

        if (! str_starts_with($name, $prefix)) {
            return null;
        }

        $suffix = substr($name, strlen($prefix));

        if ($suffix === '') {
            return null;
        }

        $segments = explode(':', $suffix);
        $index = array_shift($segments);

        if (! is_string($index) || $index === '' || ! ctype_digit($index)) {
            return null;
        }

        return [(int) $index, $segments];
    }

    protected function resolveShortLink(string $slug): ?string
    {
        if ($slug === '' || $this->domain === '') {
            return null;
        }

        return $this->buildShortLink($slug);
    }

    protected function decodeEntryValue(string $payload, string $slug, string $key, bool $allowRaw): mixed
    {
        foreach ($this->payloadCandidates($payload) as $candidate) {
            [$decoded, $valid] = $this->tryDecodeJson($candidate);

            if ($valid) {
                return $decoded;
            }
        }

        if ($allowRaw) {
            return $payload;
        }

        Log::warning('Failed to decode Cloudflare link log payload', [
            'slug' => $slug,
            'key' => $key,
        ]);

        return null;
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
}
