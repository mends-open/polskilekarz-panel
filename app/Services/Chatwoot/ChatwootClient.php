<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
class ChatwootClient extends Service
{
    public function __construct(Factory $http, ?string $endpoint = null)
    {
        parent::__construct($http, $endpoint);
    }

    public function platform(?string $accessToken = null): Platform
    {
        return new Platform($this->http, $this->endpoint, $accessToken);
    }

    public function application(?string $accessToken = null): Application
    {
        return new Application($accessToken, $this->http, $this->endpoint);
    }

}
