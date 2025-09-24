<?php

namespace App\Services\Chatwoot\Concerns;

use BadMethodCallException;
use RuntimeException;

trait HandlesResources
{
    /**
     * @var array<string, object>
     */
    private array $resolvedResources = [];

    /**
     * @return array<string, class-string>
     */
    abstract protected function resources(): array;

    public function __get(string $name): object
    {
        $resources = $this->resources();

        if (! array_key_exists($name, $resources)) {
            throw new RuntimeException(sprintf('Chatwoot resource [%s] is not defined.', $name));
        }

        if (! isset($this->resolvedResources[$name])) {
            $class = $resources[$name];
            $this->resolvedResources[$name] = new $class($this);
        }

        return $this->resolvedResources[$name];
    }

    public function __call(string $name, array $arguments): object
    {
        if ($arguments === []) {
            return $this->__get($name);
        }

        throw new BadMethodCallException(sprintf('Chatwoot resource [%s] does not accept arguments.', $name));
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->resources());
    }
}
