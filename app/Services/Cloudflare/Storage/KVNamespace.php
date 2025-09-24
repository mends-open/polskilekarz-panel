<?php

namespace App\Services\Cloudflare\Storage;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;

class KVNamespace
{
    public function __construct(
        protected Factory $http,
        protected string $endpoint,
        protected string $token,
        protected string $accountId,
        protected string $namespaceId,
        protected ?string $defaultDomain = null,
    ) {
    }

    public function create(string $key, string $value, array $headers = []): KVResult
    {
        $request = $this->baseRequest()
            ->withHeaders(array_merge([
                'Content-Type' => 'text/plain',
            ], $headers));

        $response = $request->send('PUT', $this->buildPath("values/{$key}"), [
            'body' => base64_encode($value),
        ]);

        return $this->result($key, $response);
    }

    public function createIfAbsent(string $key, string $value): KVResult
    {
        return $this->create($key, $value, ['If-None-Match' => '*']);
    }

    public function retrieve(string $key): ?string
    {
        $response = $this->baseRequest()->send('GET', $this->buildPath("values/{$key}"));

        if ($response->failed()) {
            return null;
        }

        $decoded = base64_decode($response->body(), true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listKeys(array $query = []): array
    {
        $response = $this->baseRequest()
            ->acceptJson()
            ->get($this->buildPath('keys'), $query);

        if ($response->failed()) {
            return [];
        }

        $result = $response->json('result');

        return is_array($result) ? $result : [];
    }

    protected function result(string $key, Response $response): KVResult
    {
        return new KVResult($key, $response, $this->defaultDomain);
    }

    public function buildShortLink(string $key, ?string $domain = null): string
    {
        $domain ??= $this->defaultDomain;

        if ($domain === null || $domain === '') {
            throw new InvalidArgumentException('Short link domain is not configured.');
        }

        return rtrim($domain, '/') . '/' . $key;
    }

    protected function baseRequest(): PendingRequest
    {
        return $this->http->baseUrl($this->endpoint)
            ->withToken($this->token);
    }

    protected function buildPath(string $suffix): string
    {
        return sprintf(
            'accounts/%s/storage/kv/namespaces/%s/%s',
            $this->accountId,
            $this->namespaceId,
            ltrim($suffix, '/'),
        );
    }
}
