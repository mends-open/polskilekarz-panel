<?php

declare(strict_types=1);

use App\Services\Cloudflare\CloudflareClient;
use App\Services\Cloudflare\LinkShortener;
use App\Services\Cloudflare\Storage\KVNamespace;
use Illuminate\Http\Client\Factory;

it('loads aggregated Cloudflare link entries from the primary namespace', function () {
    $entries = [
        'alpha:entries' => base64_encode(gzencode(json_encode([
            'slug' => 'alpha',
            'url' => 'https://destination.test/alpha',
            'short_url' => 'https://short.test/alpha',
            'total' => 2,
            'entries' => [
                [
                    'identifier' => '018fba1d-56b7-7c9b-b05e-31b89d812345',
                    'timestamp' => '2024-10-01T10:00:00Z',
                    'request_id' => 'req-1',
                    'request' => [
                        'method' => 'GET',
                        'url' => 'https://worker.test/alpha',
                    ],
                    'response' => [
                        'status' => 302,
                    ],
                ],
                [
                    'identifier' => '018fba1d-56b8-7d0a-b37c-31b89d876543',
                    'timestamp' => '2024-10-01T10:05:00Z',
                    'request_id' => 'req-2',
                    'request' => [
                        'method' => 'GET',
                        'url' => 'https://worker.test/alpha',
                    ],
                    'response' => [
                        'status' => 302,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR))),
    ];

    $client = new FakeCloudflareClient($entries);
    $shortener = new LinkShortener($client);

    $result = $shortener->entries('alpha');

    expect($result['slug'])->toBe('alpha');
    expect($result['url'])->toBe('https://destination.test/alpha');
    expect($result['short_url'])->toBe('https://short.test/alpha');
    expect($result['total'])->toBe(2);
    expect($result['entries'])->toHaveCount(2);
    expect($result['entries'][0]['identifier'])->toBe('018fba1d-56b7-7c9b-b05e-31b89d812345');
    expect($result['entries'][1]['request']['url'])->toBe('https://worker.test/alpha');
});

it('loads distributed Cloudflare link entries stored under individual keys', function () {
    $entries = [
        'gamma:entries:019a0637-f88b-7f04-8da5-47f004d23a24' => json_encode([
            'identifier' => '019a0637-f88b-7f04-8da5-47f004d23a24',
            'timestamp' => '2024-11-02T10:00:00Z',
            'request_id' => 'req-100',
            'request' => [
                'method' => 'GET',
                'url' => 'https://worker.test/gamma',
            ],
            'response' => [
                'status' => 302,
            ],
            'slug' => 'gamma',
            'url' => 'https://destination.test/gamma',
            'short_url' => 'https://short.test/gamma',
        ], JSON_THROW_ON_ERROR),
        'gamma:entries:019a0637-f88b-7f04-8da5-47f004d23a25' => json_encode([
            'identifier' => '019a0637-f88b-7f04-8da5-47f004d23a25',
            'timestamp' => '2024-11-02T10:05:00Z',
            'request_id' => 'req-101',
            'request' => [
                'method' => 'GET',
                'url' => 'https://worker.test/gamma',
            ],
            'response' => [
                'status' => 302,
            ],
            'slug' => 'gamma',
            'url' => 'https://destination.test/gamma',
            'short_url' => 'https://short.test/gamma',
        ], JSON_THROW_ON_ERROR),
    ];

    $client = new FakeCloudflareClient($entries);
    $shortener = new LinkShortener($client);

    $result = $shortener->entries('gamma');

    expect($result['slug'])->toBe('gamma');
    expect($result['url'])->toBe('https://destination.test/gamma');
    expect($result['short_url'])->toBe('https://short.test/gamma');
    expect($result['total'])->toBe(2);
    expect($result['entries'])->toHaveCount(2);
    expect($result['entries'][0]['identifier'])->toBe('019a0637-f88b-7f04-8da5-47f004d23a25');
    expect($result['entries'][1]['identifier'])->toBe('019a0637-f88b-7f04-8da5-47f004d23a24');
});

it('derives totals when the payload omits them', function () {
    $entries = [
        'beta:entries' => json_encode([
            'entries' => [
                ['identifier' => '0'],
                ['identifier' => '1'],
            ],
        ], JSON_THROW_ON_ERROR),
    ];

    $client = new FakeCloudflareClient($entries);
    $shortener = new LinkShortener($client);

    $result = $shortener->entries('beta');

    expect($result['total'])->toBe(2);
    expect($result['entries'])->toHaveCount(2);
});

it('returns an empty structure when the links namespace is not configured', function () {
    $client = new FakeCloudflareClient([], [
        'shortener' => [
            'links_namespace_id' => '',
            'domain' => '',
        ],
    ]);

    $shortener = new LinkShortener($client);

    $result = $shortener->entries('beta', 'https://destination.test/beta');

    expect($result['slug'])->toBe('beta');
    expect($result['entries'])->toBe([]);
    expect($result['total'])->toBe(0);
    expect($result['short_url'])->toBeNull();
});

class FakeCloudflareClient extends CloudflareClient
{
    public function __construct(private array $store, private array $overrides = [])
    {
        parent::__construct(new Factory);
    }

    protected function configuration(): array
    {
        $base = [
            'endpoint' => 'https://api.cloudflare.test',
            'api_token' => 'token',
            'account_id' => 'account',
            'shortener' => [
                'links_namespace_id' => 'links',
                'domain' => 'https://short.test',
                'slug_length' => 6,
            ],
        ];

        return array_replace_recursive($base, $this->overrides);
    }

    public function kv(string $namespaceId, array $options = []): KVNamespace
    {
        if ($namespaceId === 'links') {
            return new FakeKVNamespace($this->store);
        }

        return parent::kv($namespaceId, $options);
    }
}

class FakeKVNamespace extends KVNamespace
{
    public function __construct(private array $store)
    {
        parent::__construct(new Factory, 'https://api.cloudflare.test', 'token', 'account', 'links', null);
    }

    public function retrieve(string $key): ?string
    {
        if (! array_key_exists($key, $this->store)) {
            return null;
        }

        $value = $this->store[$key];

        $decoded = base64_decode($value, true);

        if ($decoded !== false) {
            return $decoded;
        }

        return $value;
    }

    public function listKeys(array $query = []): array
    {
        $prefix = (string) ($query['prefix'] ?? '');
        $limit = isset($query['limit']) ? (int) $query['limit'] : null;

        $keys = array_keys($this->store);

        if ($prefix !== '') {
            $keys = array_values(array_filter($keys, fn ($key) => str_starts_with($key, $prefix)));
        }

        sort($keys);

        if ($limit !== null && $limit > 0) {
            $keys = array_slice($keys, 0, $limit);
        }

        return array_map(fn ($key) => ['name' => $key], $keys);
    }
}
