<?php

namespace App\Services\Chatwoot;

use Illuminate\Http\Client\Factory;
use RuntimeException;

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

    public function __get(string $name): Service
    {
        if (! method_exists($this, $name)) {
            throw new RuntimeException(sprintf('Chatwoot entrypoint [%s] is not defined.', $name));
        }

        return $this->{$name}();
    }
}
