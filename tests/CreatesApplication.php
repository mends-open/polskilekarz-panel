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

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
