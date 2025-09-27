<?php

namespace App\Services\Chatwoot\Resources;

use Illuminate\Http\Client\Response;
use RuntimeException;

abstract class Resource
{
    protected function decodeResponse(Response $response, string $errorMessage): array
    {
        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException($errorMessage);
        }

        return $data;
    }
}
