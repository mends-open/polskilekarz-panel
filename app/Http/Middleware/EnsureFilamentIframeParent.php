<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureFilamentIframeParent
{
    public function __construct(
        private readonly Repository $config,
    ) {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = $this->normalizeConfiguredOrigins(
            $this->config->get('filament.app.allowed_iframe_parents')
        );

        if ($allowedOrigins === []) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Filament iframe parent origins not configured.',
            );
        }

        $referer = $request->headers->get('referer');

        if ($referer === null) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                'Filament panel must be loaded from the dashboard application.',
            );
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);

        if (blank($refererHost)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                'Filament panel must be loaded from the dashboard application.',
            );
        }

        $refererHost = strtolower($refererHost);
        $requestHost = parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST);

        if (is_string($requestHost) && $refererHost === strtolower($requestHost)) {
            return $next($request);
        }

        foreach ($allowedOrigins as $origin) {
            if ($this->hostMatchesOrigin($refererHost, $origin)) {
                return $next($request);
            }
        }

        throw new HttpException(
            Response::HTTP_FORBIDDEN,
            'Filament panel must be loaded from the dashboard application.',
        );
    }

    /**
     * @param  array<int, string>|string|null  $configuredOrigins
     * @return array<int, array{host: string, wildcard: bool}>
     */
    private function normalizeConfiguredOrigins($configuredOrigins): array
    {
        if (is_string($configuredOrigins)) {
            $configuredOrigins = array_map('trim', explode(',', $configuredOrigins));
        }

        if (! is_array($configuredOrigins)) {
            return [];
        }

        $normalized = [];

        foreach ($configuredOrigins as $origin) {
            if (! is_string($origin)) {
                continue;
            }

            $origin = trim($origin);

            if ($origin === '') {
                continue;
            }

            $components = parse_url($origin);

            if (! is_array($components)) {
                throw new HttpException(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'Invalid Filament iframe parent origin configuration.',
                );
            }

            $scheme = strtolower((string) ($components['scheme'] ?? ''));
            $host = strtolower((string) ($components['host'] ?? ''));

            if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
                throw new HttpException(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'Invalid Filament iframe parent origin configuration.',
                );
            }

            $isWildcard = str_starts_with($host, '*.');

            if ($isWildcard) {
                $host = substr($host, 2);

                if ($host === false || $host === '') {
                    throw new HttpException(
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        'Invalid Filament iframe parent origin configuration.',
                    );
                }
            }

            $normalized[] = [
                'host' => $host,
                'wildcard' => $isWildcard,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array{host: string, wildcard: bool}  $origin
     */
    private function hostMatchesOrigin(string $refererHost, array $origin): bool
    {
        if ($origin['wildcard']) {
            return str_ends_with($refererHost, '.' . $origin['host']);
        }

        return $refererHost === $origin['host'];
    }
}
