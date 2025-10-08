<?php

namespace App\Services\Cloudflare\Storage;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use JsonException;

class KVNamespace
{
    public function __construct(
        protected Factory $http,
        protected string $endpoint,
        protected string $token,
        protected string $accountId,
        protected string $namespaceId,
        protected ?string $defaultDomain = null,
    ) {}

    /**
     * @param  array<string, string>  $metadata
     */
    public function create(string $key, string $value, array $headers = [], array $metadata = []): KVResult
    {
        $request = $this->baseRequest();

        $options = [];

        if ($metadata !== []) {
            if ($headers !== []) {
                $request = $request->withHeaders($headers);
            }

            $options['multipart'] = $this->multipartPayload($value, $metadata);
        } else {
            $request = $request->withHeaders(array_merge([
                'Content-Type' => 'text/plain',
            ], $headers));

            $options['body'] = $value;
        }

        $response = $request->send('PUT', $this->buildPath("values/{$key}"), $options);

        return $this->result($key, $response);
    }

    /**
     * @param  array<string, string>  $metadata
     */
    public function createIfAbsent(string $key, string $value, array $metadata = []): KVResult
    {
        return $this->create($key, $value, ['If-None-Match' => '*'], $metadata);
    }

    /**
     * @param  array<string, string>  $metadata
     * @return array<int, array<string, mixed>>
     */
    protected function multipartPayload(string $value, array $metadata): array
    {
        try {
            $encodedMetadata = json_encode($metadata, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            report($exception);

            $encodedMetadata = '{}';
        }

        return [
            [
                'name' => 'value',
                'contents' => $value,
            ],
            [
                'name' => 'metadata',
                'contents' => $encodedMetadata,
            ],
        ];
    }

    public function retrieve(string $key): ?string
    {
        $response = $this->baseRequest()->send('GET', $this->buildPath("values/{$key}"));

        if ($response->failed()) {
            return null;
        }

        $body = $response->body();

        $decoded = base64_decode($body, true);

        if ($decoded !== false) {
            return $decoded;
        }

        return $body;
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

        return rtrim($domain, '/').'/'.$key;
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
