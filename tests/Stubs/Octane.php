<?php

namespace Tests\Stubs;

class Octane
{
    public static function prepareApplicationForNextOperation(): array
    {
        return [];
    }

    public static function prepareApplicationForNextRequest(): array
    {
        return [];
    }

    public static function defaultServicesToWarm(): array
    {
        return [];
    }
}
