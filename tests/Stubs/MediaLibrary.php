<?php

namespace Spatie\MediaLibrary;

if (! interface_exists(HasMedia::class)) {
    interface HasMedia {}
}

if (! trait_exists(InteractsWithMedia::class)) {
    trait InteractsWithMedia {}
}
