<?php

namespace App\Services\Cloudflare\Storage;

use Illuminate\Http\Client\Response;
use InvalidArgumentException;

class KVResult
{
    public function __construct(
        protected string $key,
        protected Response $response,
        protected ?string $defaultDomain = null,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function response(): Response
    {
        return $this->response;
    }

    public function successful(): bool
    {
        return $this->response->successful();
    }

    public function failed(): bool
    {
        return $this->response->failed();
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function conflicted(): bool
    {
        return $this->status() === 412;
    }

    public function created(): bool
    {
        return $this->successful() && ! $this->conflicted();
    }

    public function buildShortLink(?string $domain = null): string
    {
        $domain ??= $this->defaultDomain;

        if ($domain === null || $domain === '') {
            throw new InvalidArgumentException('Short link domain is not configured.');
        }

        return rtrim($domain, '/') . '/' . $this->key;
    }
}
