<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureChatwootIframeParent
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedParents = $this->getAllowedParents();

        if ($allowedParents === []) {
            throw new HttpException(
                500,
                'The Filament panel requires the CHATWOOT_DASHBOARD_PARENT_URL environment variable to be configured.',
            );
        }

        if ($this->shouldVerifyRequest($request) && ! $this->isRequestVerified($request)) {
            $loadedFromAllowedParent = $this->requestMatchesAllowedParents($request, $allowedParents);

            if (! $loadedFromAllowedParent && ! $this->requestMatchesApplication($request)) {
                throw new HttpException(
                    403,
                    'The Filament panel must be loaded inside the Chatwoot Dashboard App iframe after / but inside iframe',
                );
            }

            if ($loadedFromAllowedParent) {
                $this->markRequestAsVerified($request);
            }
        }

        /** @var Response $response */
        $response = $next($request);

        $this->addFrameAncestorsHeader($response, $allowedParents);

        return $response;
    }

    private function shouldVerifyRequest(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if (! str_contains($request->headers->get('Accept', ''), 'text/html')) {
            return false;
        }

        $site = $request->headers->get('Sec-Fetch-Site');

        if (in_array($site, ['same-origin', 'same-site'], true)) {
            return false;
        }

        return true;
    }

    private function isRequestVerified(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        return (bool) $request->session()->get('chatwoot_iframe_parent_verified', false);
    }

    private function markRequestAsVerified(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put('chatwoot_iframe_parent_verified', true);
    }

    /**
     * @param  string[]  $allowedParents
     */
    private function requestMatchesAllowedParents(Request $request, array $allowedParents): bool
    {
        foreach (['Referer', 'Origin'] as $header) {
            $value = $request->headers->get($header);

            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($this->valueMatchesAllowedParents($value, $allowedParents)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string[]  $allowedParents
     */
    private function valueMatchesAllowedParents(string $value, array $allowedParents): bool
    {
        foreach ($allowedParents as $allowedParent) {
            if ($this->valuesShareOrigin($value, $allowedParent)) {
                return true;
            }
        }

        return false;
    }

    private function valuesShareOrigin(string $candidate, string $allowedParent): bool
    {
        $candidateOrigin = $this->parseOrigin($candidate);
        $allowedOrigin = $this->parseOrigin($allowedParent);

        if ($candidateOrigin === null || $allowedOrigin === null) {
            return $this->relaxedPrefixMatch($candidate, $allowedParent);
        }

        if ($allowedOrigin['scheme'] !== null && $candidateOrigin['scheme'] !== null
            && $allowedOrigin['scheme'] !== $candidateOrigin['scheme']) {
            return false;
        }

        if ($candidateOrigin['host'] !== $allowedOrigin['host']) {
            return false;
        }

        if ($allowedOrigin['port'] !== null && $candidateOrigin['port'] !== null
            && $allowedOrigin['port'] !== $candidateOrigin['port']) {
            return false;
        }

        return true;
    }

    private function parseOrigin(string $value): ?array
    {
        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : null;
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? $this->defaultPortForScheme($scheme);

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
        ];
    }

    private function relaxedPrefixMatch(string $candidate, string $allowedParent): bool
    {
        $trimmed = rtrim($allowedParent, '/');

        if (str_starts_with($candidate, $allowedParent)) {
            return true;
        }

        return $trimmed !== '' && str_starts_with($candidate, $trimmed);
    }

    private function requestMatchesApplication(Request $request): bool
    {
        $applicationOrigin = $request->getSchemeAndHttpHost();

        foreach (['Origin', 'Referer'] as $header) {
            $value = $request->headers->get($header);

            if (is_string($value) && str_starts_with($value, $applicationOrigin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string[]  $allowedParents
     */
    private function addFrameAncestorsHeader(Response $response, array $allowedParents): void
    {
        $directive = 'frame-ancestors ' . implode(' ', $allowedParents);

        $existing = $response->headers->get('Content-Security-Policy');

        if ($existing === null) {
            $response->headers->set('Content-Security-Policy', $directive);

            return;
        }

        if (str_contains($existing, 'frame-ancestors')) {
            $updated = preg_replace('/frame-ancestors[^;]*/', $directive, $existing, 1);
            $response->headers->set('Content-Security-Policy', $updated ?? $directive);

            return;
        }

        $response->headers->set('Content-Security-Policy', rtrim($existing, '; ') . '; ' . $directive);
    }

    /**
     * @return array<int, string>
     */
    private function getAllowedParents(): array
    {
        $configured = config('filament.app.chatwoot_iframe_parent');

        $candidates = is_array($configured) ? $configured : [$configured];

        $allowedParents = [];

        foreach ($candidates as $value) {
            if (! is_string($value)) {
                continue;
            }

            $segments = preg_split('/[\s,]+/', $value) ?: [];

            foreach ($segments as $segment) {
                $segment = trim($segment);

                if ($segment !== '') {
                    $allowedParents[] = $segment;
                }
            }
        }

        return array_values(array_unique($allowedParents));
    }

    private function defaultPortForScheme(?string $scheme): ?int
    {
        return match ($scheme) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }
}
