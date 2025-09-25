<?php

declare(strict_types=1);

use App\Services\Cloudflare\CloudflareClient;
use App\Services\Cloudflare\LinkShortener;
use App\Services\Cloudflare\Storage\KVNamespace;
use Illuminate\Http\Client\Factory;

it('merges Cloudflare link entry logs into a single payload structure', function () {
    $entries = [
        'alpha:0' => base64_encode(gzencode(json_encode([
            'slug' => 'alpha',
            'timestamp' => '2024-10-01T10:00:00Z',
            'request' => [
                'method' => 'GET',
                'url' => 'https://worker.test/alpha',
            ],
            'response' => [
                'status' => 302,
            ],
        ], JSON_THROW_ON_ERROR))),
        'alpha:1' => base64_encode(gzencode(json_encode([
            'slug' => 'alpha',
            'timestamp' => '2024-10-01T10:05:00Z',
            'request' => [
                'method' => 'GET',
                'url' => 'https://worker.test/alpha',
            ],
            'response' => [
                'status' => 302,
            ],
        ], JSON_THROW_ON_ERROR))),
        'alpha:counter' => '2',
    ];

    $client = new FakeCloudflareClient($entries);
    $shortener = new LinkShortener($client);

    $result = $shortener->entry('alpha', 'https://destination.test');

    expect($result['slug'])->toBe('alpha');
    expect($result['url'])->toBe('https://destination.test');
    expect($result['short_url'])->toBe('https://short.test/alpha');
    expect($result['total'])->toBe(2);
    expect($result['entries'])->toHaveCount(2);

    expect($result['entries'][0]['index'])->toBe(0);
    expect($result['entries'][0]['timestamp'])->toBe('2024-10-01T10:00:00Z');
    expect($result['entries'][0]['request']['method'])->toBe('GET');

    expect($result['entries'][1]['index'])->toBe(1);
    expect($result['entries'][1]['timestamp'])->toBe('2024-10-01T10:05:00Z');
});

it('handles plain JSON payloads without compression', function () {
    $entries = [
        'beta:0' => json_encode([
            'slug' => 'beta',
            'timestamp' => '2024-10-02T11:00:00Z',
            'request' => [
                'method' => 'GET',
                'url' => 'https://worker.test/beta',
            ],
            'response' => [
                'status' => 302,
            ],
        ], JSON_THROW_ON_ERROR),
        'beta:counter' => '1',
    ];

    $client = new FakeCloudflareClient($entries);
    $shortener = new LinkShortener($client);

    $result = $shortener->entry('beta');

    expect($result['entries'])->toHaveCount(1);
    expect($result['entries'][0]['timestamp'])->toBe('2024-10-02T11:00:00Z');
    expect($result['total'])->toBe(1);
});

it('returns an empty structure when the logs namespace is not configured', function () {
    $client = new FakeCloudflareClient([], [
        'links' => [
            'logs_namespace_id' => '',
            'domain' => '',
        ],
    ]);

    $shortener = new LinkShortener($client);

    $result = $shortener->entry('beta', 'https://destination.test/beta');

    expect($result['slug'])->toBe('beta');
    expect($result['entries'])->toBe([]);
    expect($result['total'])->toBe(0);
    expect($result['short_url'])->toBeNull();
});

class FakeCloudflareClient extends CloudflareClient
{
    public function __construct(private array $store, private array $overrides = [])
    {
        parent::__construct(new Factory());
    }

    protected function configuration(): array
    {
        $base = [
            'endpoint' => 'https://api.cloudflare.test',
            'api_token' => 'token',
            'account_id' => 'account',
            'links' => [
                'namespace_id' => 'links',
                'domain' => 'https://short.test',
                'slug_length' => 6,
                'logs_namespace_id' => 'logs',
            ],
        ];

        return array_replace_recursive($base, $this->overrides);
    }

    public function kv(string $namespaceId, array $options = []): KVNamespace
    {
        if ($namespaceId === 'logs') {
            return new FakeKVNamespace($this->store);
        }

        return parent::kv($namespaceId, $options);
    }
}

class FakeKVNamespace extends KVNamespace
{
    public function __construct(private array $store)
    {
        parent::__construct(new Factory(), 'https://api.cloudflare.test', 'token', 'account', 'logs', null);
    }

    public function listKeys(array $query = []): array
    {
        $prefix = $query['prefix'] ?? '';

        $keys = [];

        foreach (array_keys($this->store) as $name) {
            if ($prefix === '' || str_starts_with($name, $prefix)) {
                $keys[] = ['name' => $name];
            }
        }

        return $keys;
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
}
