<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

if (! interface_exists(\Spatie\MediaLibrary\HasMedia::class) || ! trait_exists(\Spatie\MediaLibrary\InteractsWithMedia::class)) {
    require_once __DIR__.'/../tests/Stubs/MediaLibrary.php';
}

if (! class_exists(\Spatie\MediaLibrary\MediaCollections\Models\Media::class)) {
    require_once __DIR__.'/../tests/Stubs/MediaCollections.php';
}

if (! trait_exists(\Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat::class)) {
    require_once __DIR__.'/../tests/Stubs/PostgresqlEnhanced.php';
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/events',
            'cloudflare/link-clicks',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
