<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureFilamentIframeParent;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

function makeMiddleware(?string $parentUrl): EnsureFilamentIframeParent
{
    $config = new ConfigRepository([
        'filament' => [
            'app' => [
                'chatwoot_iframe_parent' => $parentUrl,
            ],
        ],
    ]);

    return new EnsureFilamentIframeParent($config);
}

test('it throws an internal error when the parent url is missing', function (): void {
    $middleware = makeMiddleware(null);
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Filament parent URL not configured.');
});

test('it throws an internal error when the parent url is invalid', function (): void {
    $middleware = makeMiddleware('not-a-url');
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Invalid Filament parent URL configuration.');
});

test('it returns forbidden when the request is missing a referer', function (): void {
    $middleware = makeMiddleware('https://chatwoot.example');
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Filament panel must be loaded from the dashboard application.');
});

test('it allows requests that originate from the configured parent', function (): void {
    $middleware = makeMiddleware('https://chatwoot.example');
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://chatwoot.example/dashboard',
    ]);

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('it allows requests that originate from the filament panel itself', function (): void {
    $middleware = makeMiddleware('https://chatwoot.example');
    $request = Request::create('https://panel.test/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://panel.test/some-page',
    ]);

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});
