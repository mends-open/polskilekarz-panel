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
        $allowedParent = trim((string) config('filament.app.chatwoot_iframe_parent'));

        if ($allowedParent === '') {
            return $next($request);
        }

        if ($this->shouldBlockRequest($request, $allowedParent)) {
            throw new HttpException(403, 'The Filament panel must be loaded inside the Chatwoot dashboard iframe.');
        }

        /** @var Response $response */
        $response = $next($request);

        $this->addFrameAncestorsHeader($response, $allowedParent);

        return $response;
    }

    private function shouldBlockRequest(Request $request, string $allowedParent): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $acceptHeader = $request->headers->get('Accept', '');

        if (! str_contains($acceptHeader, 'text/html')) {
            return false;
        }

        $destination = $request->headers->get('Sec-Fetch-Dest');

        if ($destination !== null) {
            return ! in_array($destination, ['iframe', 'frame'], true);
        }

        $referer = $request->headers->get('Referer', '');

        if ($referer === '') {
            return true;
        }

        return ! str_starts_with($referer, $allowedParent);
    }

    private function addFrameAncestorsHeader(Response $response, string $allowedParent): void
    {
        $directive = "frame-ancestors {$allowedParent}";

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
}
