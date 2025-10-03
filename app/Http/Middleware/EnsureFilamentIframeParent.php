<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

readonly class EnsureFilamentIframeParent
{
    public function __construct(
        private Repository $config,
    )
    {
    }

    /**
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $parentUrl = $this->config->get('filament.app.chatwoot_iframe_parent');

        if (blank($parentUrl)) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $parentHost = parse_url($parentUrl, PHP_URL_HOST);

        if (blank($parentHost)) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $referer = $request->headers->get('referer');

        if ($referer === null) {
            throw new HttpException(Response::HTTP_FORBIDDEN);
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $requestHost = parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST);

        if ($refererHost !== $parentHost && $refererHost !== $requestHost) {
            throw new HttpException(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
