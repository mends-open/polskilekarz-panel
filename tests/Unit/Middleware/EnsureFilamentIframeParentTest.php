<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureFilamentIframeParent;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

function makeMiddleware(array|string|null $origins): EnsureFilamentIframeParent
{
    $config = new ConfigRepository([
        'filament' => [
            'app' => [
                'allowed_iframe_parents' => $origins,
            ],
        ],
    ]);

    return new EnsureFilamentIframeParent($config);
}

test('it throws an internal error when no iframe parents are configured', function (): void {
    $middleware = makeMiddleware(null);
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Filament iframe parent origins not configured.');
});

test('it throws an internal error when the configured iframe parent is invalid', function (): void {
    $middleware = makeMiddleware('not-a-url');
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Invalid Filament iframe parent origin configuration.');
});

test('it returns forbidden when the request is missing a referer', function (): void {
    $middleware = makeMiddleware(['https://chatwoot.example']);
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Filament panel must be loaded from the dashboard application.');
});

test('it allows requests that originate from the configured parent', function (): void {
    $middleware = makeMiddleware(['https://chatwoot.example']);
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://chatwoot.example/dashboard',
    ]);

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('it allows requests that originate from the filament panel itself', function (): void {
    $middleware = makeMiddleware(['https://chatwoot.example']);
    $request = Request::create('https://panel.test/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://panel.test/some-page',
    ]);

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('it allows requests from any configured iframe parent', function (): void {
    $middleware = makeMiddleware('https://chatwoot.example, https://other.example');
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://other.example/some-path',
    ]);

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('it allows requests from wildcard iframe parents', function (): void {
    $middleware = makeMiddleware(['https://*.example.com']);
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://api.eu.example.com/dashboard',
    ]);

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('it forbids requests that do not match configured iframe parents', function (): void {
    $middleware = makeMiddleware(['https://chatwoot.example', 'https://*.example.com']);
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_REFERER' => 'https://malicious.test/hack',
    ]);

    expect(fn () => $middleware->handle($request, fn (): Response => new Response()))
        ->toThrow(HttpException::class, 'Filament panel must be loaded from the dashboard application.');
});
