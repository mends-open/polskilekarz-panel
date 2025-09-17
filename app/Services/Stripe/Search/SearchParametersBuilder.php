<?php

namespace App\Services\Stripe\Search;

class SearchParametersBuilder
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(string $query, array $options): array
    {
        $payload = ['query' => $query];

        if (isset($options['expand']) && $options['expand'] !== []) {
            $payload['expand'] = array_values(array_map('strval', $options['expand']));
        }

        if (isset($options['limit'])) {
            $payload['limit'] = (int) $options['limit'];
        }

        if (isset($options['page'])) {
            $payload['page'] = (string) $options['page'];
        }

        return $payload;
    }
}
