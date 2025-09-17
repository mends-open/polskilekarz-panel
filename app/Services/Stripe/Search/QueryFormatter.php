<?php

namespace App\Services\Stripe\Search;

use Illuminate\Support\Str;

class QueryFormatter
{
    public function buildQueryClause(string $field, string $value, string $operator = ':'): string
    {
        $field = Str::of($field)->trim()->toString();
        $op = Str::of($operator)->trim()->lower()->toString();
        $op = $op === '' ? ':' : $op;

        if ($op === 'has' || $op === 'has:') {
            return 'has:' . $field;
        }

        $normalized = Str::of($value)->trim();

        if ($normalized->isEmpty()) {
            $formatted = "''";
        } elseif (is_numeric($normalized->toString())) {
            $formatted = $normalized->toString();
        } else {
            $escaped = Str::of($normalized->toString())
                ->replace('\\', '\\\\')
                ->replace("'", "\\'")
                ->toString();
            $formatted = "'" . $escaped . "'";
        }

        return $field . $op . $formatted;
    }

    public function metadataField(string $field): string
    {
        $key = Str::of($field)->trim();
        $escaped = $key
            ->replace('\\', '\\\\')
            ->replace("'", "\\'")
            ->toString();

        return "metadata['{$escaped}']";
    }
}
