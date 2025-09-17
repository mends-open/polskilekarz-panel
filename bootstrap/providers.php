<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

if (class_exists(\Filament\PanelProvider::class)) {
    $providers[] = App\Providers\Filament\AppPanelProvider::class;
}

if (class_exists(\Laravel\Horizon\HorizonApplicationServiceProvider::class)) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

return $providers;
