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

        if ($slug === '' || $this->logsNamespace === '') {
            return $result;
        }

        $kv = $this->cloudflare->kv($this->logsNamespace);
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
        $keys = $kv->listKeys(['prefix' => $slug . ':']);

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

            if (! $this->isEntryRecordKey($slug, $name)) {
                continue;
            }

            $payload = $kv->retrieve($name);

            if (! is_string($payload) || $payload === '') {
                continue;
            }

            $decoded = $this->decodeEntryRecord($payload, $slug, $name);

            if ($decoded === null) {
                continue;
            }

            $entries[] = [
                'key' => $name,
                'index' => $this->extractIndex($slug, $name),
            ] + $decoded;
        }

        $this->sortEntryRecords($entries);

        return [array_values($entries), $counter];
    }

    protected function decodeEntryRecord(string $payload, string $slug, string $key): ?array
    {
        foreach ($this->payloadCandidates($payload) as $candidate) {
            $decoded = $this->decodeJson($candidate);

            if ($decoded !== null) {
                return $decoded;
            }
        }

        Log::warning('Failed to decode Cloudflare link log payload', [
            'slug' => $slug,
            'key' => $key,
        ]);

        return null;
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

    protected function parseCounter(?string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    protected function entryCounterKey(string $slug): string
    {
        return $slug . ':counter';
    }

    protected function isEntryRecordKey(string $slug, string $name): bool
    {
        return str_starts_with($name, $slug . ':');
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
        yield $payload;

        if ($this->isGzipPayload($payload)) {
            $decoded = gzdecode($payload);

            if ($decoded !== false) {
                yield $decoded;
            }
        }
    }

    protected function isGzipPayload(string $payload): bool
    {
        return strlen($payload) >= 2 && str_starts_with($payload, "\x1F\x8B");
    }

    protected function decodeJson(string $payload): ?array
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    protected function resolveShortLink(string $slug): ?string
    {
        if ($slug === '' || $this->domain === '') {
            return null;
        }

        return $this->buildShortLink($slug);
    }
}
