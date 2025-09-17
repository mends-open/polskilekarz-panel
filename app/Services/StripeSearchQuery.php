<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use InvalidArgumentException;

class StripeSearchQuery
{
    /**
     * Create an equality comparison clause.
     */
    public static function equals(string $field, mixed $value): string
    {
        return self::compare($field, ':', $value);
    }

    /**
     * Create a greater-than comparison clause.
     */
    public static function greaterThan(string $field, mixed $value): string
    {
        return self::compare($field, '>', $value);
    }

    /**
     * Create a greater-than or equal comparison clause.
     */
    public static function greaterThanOrEquals(string $field, mixed $value): string
    {
        return self::compare($field, '>=', $value);
    }

    /**
     * Create a less-than comparison clause.
     */
    public static function lessThan(string $field, mixed $value): string
    {
        return self::compare($field, '<', $value);
    }

    /**
     * Create a less-than or equal comparison clause.
     */
    public static function lessThanOrEquals(string $field, mixed $value): string
    {
        return self::compare($field, '<=', $value);
    }

    /**
     * Create an existence clause.
     */
    public static function exists(string $field): string
    {
        return sprintf("%s:'*'", $field);
    }

    /**
     * Create a metadata comparison clause.
     */
    public static function metadataEquals(string $key, mixed $value): string
    {
        $escapedKey = str_replace(["\\", "'"], ["\\\\", "\\'"], $key);

        return self::equals("metadata['{$escapedKey}']", $value);
    }

    /**
     * Combine clauses using the AND operator.
     */
    public static function all(string ...$clauses): string
    {
        return self::combine('AND', $clauses);
    }

    /**
     * Combine clauses using the OR operator.
     */
    public static function any(string ...$clauses): string
    {
        return self::combine('OR', $clauses);
    }

    /**
     * Negate a clause using the unary minus operator.
     */
    public static function not(string $clause): string
    {
        return '-' . self::wrapIfNeeded($clause);
    }

    /**
     * Group a clause in parentheses.
     */
    public static function group(string $clause): string
    {
        return '(' . trim($clause) . ')';
    }

    /**
     * Create a raw clause without any formatting.
     */
    public static function raw(string $clause): string
    {
        $trimmed = trim($clause);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Clause cannot be empty.');
        }

        return $trimmed;
    }

    /**
     * Create a comparison clause with the provided operator.
     */
    public static function compare(string $field, string $operator, mixed $value): string
    {
        $operator = trim($operator);

        if (! in_array($operator, [':', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Unsupported operator provided.');
        }

        $field = trim($field);

        if ($field === '') {
            throw new InvalidArgumentException('Field cannot be empty.');
        }

        return sprintf('%s%s%s', $field, $operator, self::formatValue($value, $operator));
    }

    /**
     * Combine clauses with a logical operator.
     */
    private static function combine(string $operator, array $clauses): string
    {
        $filtered = array_values(array_filter(array_map('trim', $clauses), fn ($clause) => $clause !== ''));

        if ($filtered === []) {
            throw new InvalidArgumentException('At least one clause must be provided.');
        }

        if (count($filtered) === 1) {
            return $filtered[0];
        }

        $wrapped = array_map(fn ($clause) => self::wrapIfNeeded($clause), $filtered);

        return implode(sprintf(' %s ', $operator), $wrapped);
    }

    /**
     * Wrap a clause in parentheses if it contains logical operators.
     */
    private static function wrapIfNeeded(string $clause): string
    {
        $trimmed = trim($clause);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Clause cannot be empty.');
        }

        $upper = strtoupper($trimmed);

        if (
            str_contains($upper, ' AND ')
            || str_contains($upper, ' OR ')
            || str_starts_with($upper, 'NOT ')
        ) {
            if (! (str_starts_with($trimmed, '(') && str_ends_with($trimmed, ')'))) {
                return '(' . $trimmed . ')';
            }
        }

        return $trimmed;
    }

    /**
     * Format values according to the Stripe Search Query Language.
     */
    private static function formatValue(mixed $value, string $operator): string
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->getTimestamp();
        }

        if (is_int($value) || is_float($value)) {
            return rtrim(rtrim(sprintf('%.15F', $value), '0'), '.');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            if ($operator !== ':' && is_numeric($value)) {
                return $value;
            }

            return '\'' . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . '\'';
        }

        throw new InvalidArgumentException('Unsupported value type provided.');
    }
}
