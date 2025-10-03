<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureChatwootIframeParent;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureChatwootIframeParentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance('config', new Repository());

        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_request_is_blocked_when_parent_not_configured(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', '');

        $request = Request::create('/filament', 'GET');

        $middleware = new EnsureChatwootIframeParent();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('The Filament panel requires the CHATWOOT_DASHBOARD_PARENT_URL environment variable to be configured.');
        $middleware->handle($request, fn () => new Response('ok'));
    }

    public function test_request_is_allowed_when_loaded_from_iframe_destination(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'iframe',
            'HTTP_SEC_FETCH_SITE' => 'cross-site',
            'HTTP_REFERER' => 'https://allowed.test/dashboard',
        ]);

        $middleware = new EnsureChatwootIframeParent();

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('frame-ancestors https://allowed.test', $response->headers->get('Content-Security-Policy'));
    }

    public function test_request_is_blocked_when_not_loaded_inside_iframe(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'document',
        ]);

        $middleware = new EnsureChatwootIframeParent();

        try {
            $middleware->handle($request, fn () => new Response('ok'));
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame(
                'The Filament panel must be loaded inside the Chatwoot Dashboard App iframe after / but inside iframe',
                $exception->getMessage(),
            );
        }
    }

    public function test_request_is_blocked_when_iframe_referer_is_not_allowed(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'iframe',
            'HTTP_SEC_FETCH_SITE' => 'cross-site',
            'HTTP_REFERER' => 'https://malicious.test/app',
        ]);

        $middleware = new EnsureChatwootIframeParent();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'The Filament panel must be loaded inside the Chatwoot Dashboard App iframe after / but inside iframe',
        );

        $middleware->handle($request, fn () => new Response('ok'));
    }

    public function test_request_is_allowed_when_origin_matches_and_referer_missing(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'iframe',
            'HTTP_SEC_FETCH_SITE' => 'cross-site',
            'HTTP_ORIGIN' => 'https://allowed.test',
        ]);

        $middleware = new EnsureChatwootIframeParent();

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('frame-ancestors https://allowed.test', $response->headers->get('Content-Security-Policy'));
    }

    public function test_request_is_allowed_for_same_origin_navigation(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'document',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ]);

        $middleware = new EnsureChatwootIframeParent();

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('frame-ancestors https://allowed.test', $response->headers->get('Content-Security-Policy'));
    }

    public function test_request_is_allowed_for_in_frame_navigation(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'document',
            'HTTP_SEC_FETCH_SITE' => 'cross-site',
            'HTTP_REFERER' => 'http://localhost/filament/dashboard',
        ]);

        $middleware = new EnsureChatwootIframeParent();

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('frame-ancestors https://allowed.test', $response->headers->get('Content-Security-Policy'));
    }
}
