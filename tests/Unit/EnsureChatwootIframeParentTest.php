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

    public function test_request_is_not_modified_when_parent_not_configured(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', '');

        $request = Request::create('/filament', 'GET');

        $middleware = new EnsureChatwootIframeParent();

        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('ok', $response->getContent());
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function test_request_is_allowed_when_loaded_from_iframe_destination(): void
    {
        config()->set('filament.app.chatwoot_iframe_parent', 'https://allowed.test');

        $request = Request::create('/filament', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_SEC_FETCH_DEST' => 'iframe',
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
            $this->assertSame('The Filament panel must be loaded inside the Chatwoot dashboard iframe.', $exception->getMessage());
        }
    }
}
