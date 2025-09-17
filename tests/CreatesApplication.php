<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        if (! class_exists(\Laravel\Octane\Octane::class)) {
            require_once __DIR__.'/Stubs/Octane.php';

            class_alias(Stubs\Octane::class, \Laravel\Octane\Octane::class);
        }

        if (! interface_exists(\Spatie\MediaLibrary\HasMedia::class) || ! trait_exists(\Spatie\MediaLibrary\InteractsWithMedia::class)) {
            require_once __DIR__.'/Stubs/MediaLibrary.php';
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
