<?php

namespace App\Exceptions;

use Exception;

class CloudflareLinkExistsException extends Exception
{
    public function __construct(public string $key, public ?string $url = null)
    {
        parent::__construct("Cloudflare link '{$key}' already exists.");
    }
}
