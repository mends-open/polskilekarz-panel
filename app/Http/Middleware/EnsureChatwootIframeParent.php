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

        if ($this->shouldBlockRequest($request, $allowedParents)) {
            throw new HttpException(
                403,
                'The Filament panel must be loaded inside the Chatwoot Dashboard App iframe after / but inside iframe',
            );
        }

        /** @var Response $response */
        $response = $next($request);

        $this->addFrameAncestorsHeader($response, $allowedParents);

        return $response;
    }

    /**
     * @param  string[]  $allowedParents
     */
    private function shouldBlockRequest(Request $request, array $allowedParents): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $acceptHeader = $request->headers->get('Accept', '');

        if (! str_contains($acceptHeader, 'text/html')) {
            return false;
        }

        if ($this->matchesAllowedParent($request, $allowedParents)) {
            return false;
        }

        if ($this->matchesApplicationOrigin($request)) {
            return false;
        }

        $destination = $request->headers->get('Sec-Fetch-Dest');

        if (in_array($destination, ['iframe', 'frame'], true)) {
            return true;
        }

        $site = $request->headers->get('Sec-Fetch-Site');

        if (in_array($site, ['same-origin', 'same-site'], true)) {
            return false;
        }

        return true;
    }

    /**
     * @param  string[]  $allowedParents
     */
    private function matchesAllowedParent(Request $request, array $allowedParents): bool
    {
        $referer = $request->headers->get('Referer');

        if (is_string($referer) && $this->candidateMatchesAllowedParents($referer, $allowedParents)) {
            return true;
        }

        $origin = $request->headers->get('Origin');

        return is_string($origin) && $this->candidateMatchesAllowedParents($origin, $allowedParents);
    }

    /**
     * @param  string[]  $allowedParents
     */
    private function candidateMatchesAllowedParents(string $candidate, array $allowedParents): bool
    {
        foreach ($allowedParents as $allowedParent) {
            if ($this->candidateMatchesAllowedParent($candidate, $allowedParent)) {
                return true;
            }
        }

        return false;
    }

    private function candidateMatchesAllowedParent(string $candidate, string $allowedParent): bool
    {
        $normalizedAllowed = $this->normalizeUrlForComparison($allowedParent);

        if ($normalizedAllowed === null) {
            return $this->rawPrefixMatch($candidate, $allowedParent);
        }

        $normalizedCandidate = $this->normalizeUrlForComparison($candidate);

        if ($normalizedCandidate === null) {
            return $this->rawPrefixMatch($candidate, $allowedParent);
        }

        if ($normalizedCandidate['host'] === null || $normalizedAllowed['host'] === null) {
            return false;
        }

        if ($normalizedAllowed['scheme'] !== null && $normalizedCandidate['scheme'] !== null
            && strcasecmp($normalizedAllowed['scheme'], $normalizedCandidate['scheme']) !== 0) {
            return false;
        }

        if (strcasecmp($normalizedCandidate['host'], $normalizedAllowed['host']) !== 0) {
            return false;
        }

        if ($normalizedAllowed['port'] !== null) {
            $candidatePort = $normalizedCandidate['port']
                ?? $this->defaultPortForScheme($normalizedCandidate['scheme']);

            $allowedPort = $normalizedAllowed['port'];

            if ($candidatePort !== $allowedPort) {
                return false;
            }
        }

        $allowedPath = $this->normalizePathForComparison($normalizedAllowed['path']);
        $candidatePath = $this->normalizePathForComparison($normalizedCandidate['path']);

        return str_starts_with($candidatePath, $allowedPath);
    }

    private function matchesApplicationOrigin(Request $request): bool
    {
        $origin = $request->headers->get('Origin');
        $referer = $request->headers->get('Referer');
        $applicationOrigin = $request->getSchemeAndHttpHost();

        foreach ([$origin, $referer] as $candidate) {
            if (is_string($candidate) && str_starts_with($candidate, $applicationOrigin)) {
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

    /**
     * @return array{scheme: string|null, host: string|null, port: int|null, path: string}|null
     */
    private function normalizeUrlForComparison(string $url): ?array
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;

        if ($host === null) {
            return null;
        }

        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : null;
        $normalizedHost = strtolower($host);
        $port = $parts['port'] ?? null;
        $path = $parts['path'] ?? '/';

        if ($path === '') {
            $path = '/';
        }

        return [
            'scheme' => $scheme,
            'host' => $normalizedHost,
            'port' => $port,
            'path' => $path,
        ];
    }

    private function normalizePathForComparison(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return rtrim($path, '/') . '/';
    }

    private function rawPrefixMatch(string $candidate, string $allowedParent): bool
    {
        foreach ($this->buildAllowedPrefixes($allowedParent) as $prefix) {
            if ($prefix !== '' && str_starts_with($candidate, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function buildAllowedPrefixes(string $allowedParent): array
    {
        $prefixes = [$allowedParent];
        $trimmed = rtrim($allowedParent, '/');

        if ($trimmed !== $allowedParent) {
            $prefixes[] = $trimmed;
        }

        return array_values(array_unique($prefixes));
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
