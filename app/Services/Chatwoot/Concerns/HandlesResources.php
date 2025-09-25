<?php

namespace App\Services\Chatwoot\Concerns;

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

    protected function resolveResource(string $name): object
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
}
