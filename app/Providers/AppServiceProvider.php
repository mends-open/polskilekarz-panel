<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap(
            collect(File::files(app_path('Models')))
                ->map(fn ($file) => $file->getBasename('.php'))
                ->mapWithKeys(fn ($name) => [Str::snake($name) => "App\\Models\\{$name}"])
                ->all()
        );
    }
}
