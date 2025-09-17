<?php

declare(strict_types=1);

namespace App\Services;

use BadMethodCallException;
use Closure;
use DateTimeInterface;
use InvalidArgumentException;

final class StripeSearchQuery
{
    private function __construct(
        private readonly string $expression,
    ) {
    }

    /**
     * Start a query for the provided field.
     */
    public static function field(string $field): PendingField
    {
        $field = trim($field);

        if ($field === '') {
            throw new InvalidArgumentException('Field cannot be empty.');
        }

        return new PendingField(
            $field,
            static fn (string $clause): self => new self($clause),
        );
    }

    /**
     * Start a query for a metadata key.
     */
    public static function metadata(string $key): PendingField
    {
        return self::field(self::metadataField($key));
    }

    /**
     * Create a query from an already formatted clause.
     */
    public static function raw(string $clause): self
    {
        $trimmed = trim($clause);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Clause cannot be empty.');
        }

        return new self($trimmed);
    }

    /**
     * Combine the current query with another clause using AND.
     */
    public function and(self|string $clause): self
    {
        return $this->combine('AND', $clause);
    }

    /**
     * Combine the current query with another clause using OR.
     */
    public function or(self|string $clause): self
    {
        return $this->combine('OR', $clause);
    }

    /**
     * Create a new clause for the AND branch using a field name.
     */
    public function andField(string $field): PendingField
    {
        return $this->pendingField('AND', $field);
    }

    /**
     * Create a new clause for the OR branch using a field name.
     */
    public function orField(string $field): PendingField
    {
        return $this->pendingField('OR', $field);
    }

    /**
     * Create a new clause for the AND branch using a metadata key.
     */
    public function andMetadata(string $key): PendingField
    {
        return $this->pendingMetadata('AND', $key);
    }

    /**
     * Create a new clause for the OR branch using a metadata key.
     */
    public function orMetadata(string $key): PendingField
    {
        return $this->pendingMetadata('OR', $key);
    }

    /**
     * Combine the current query with a grouped clause using AND.
     */
    public function andGroup(callable|string $builder): self
    {
        return $this->and(self::group($builder));
    }

    /**
     * Combine the current query with a grouped clause using OR.
     */
    public function orGroup(callable|string $builder): self
    {
        return $this->or(self::group($builder));
    }

    /**
     * Negate the current clause using Stripe's unary minus.
     */
    public function not(): self
    {
        return new self('-' . self::wrapIfNeeded($this->expression));
    }

    /**
     * Wrap the current clause in parentheses.
     */
    public function grouped(): self
    {
        return new self('(' . $this->expression . ')');
    }

    /**
     * Build a grouped clause using the provided callback or raw clause.
     *
     * @param callable(): (self|string)|string $builder
     */
    public static function group(callable|string $builder): self
    {
        $result = is_string($builder) ? $builder : $builder();

        $query = self::ensureQuery($result);

        return $query->grouped();
    }

    /**
     * Combine clauses with the AND operator.
     */
    public static function all(string ...$clauses): string
    {
        return self::combineMany('AND', $clauses);
    }

    /**
     * Combine clauses with the OR operator.
     */
    public static function any(string ...$clauses): string
    {
        return self::combineMany('OR', $clauses);
    }

    /**
     * Negate a clause using the unary minus operator.
     */
    public static function negate(string $clause): string
    {
        return self::raw($clause)->not()->toString();
    }

    /**
     * Convenience method mirroring the previous static helpers.
     */
    public static function equals(string $field, mixed $value): string
    {
        return self::field($field)->equals($value)->toString();
    }

    public static function greaterThan(string $field, mixed $value): string
    {
        return self::field($field)->greaterThan($value)->toString();
    }

    public static function greaterThanOrEquals(string $field, mixed $value): string
    {
        return self::field($field)->greaterThanOrEquals($value)->toString();
    }

    public static function lessThan(string $field, mixed $value): string
    {
        return self::field($field)->lessThan($value)->toString();
    }

    public static function lessThanOrEquals(string $field, mixed $value): string
    {
        return self::field($field)->lessThanOrEquals($value)->toString();
    }

    public static function exists(string $field): string
    {
        return self::field($field)->exists()->toString();
    }

    public static function metadataEquals(string $key, mixed $value): string
    {
        return self::metadata($key)->equals($value)->toString();
    }

    /**
     * Represent the query as a string.
     */
    public function toString(): string
    {
        return $this->expression;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private static function ensureQuery(self|string $query): self
    {
        if (is_string($query)) {
            return self::raw($query);
        }

        return $query;
    }

    private static function combineMany(string $operator, array $clauses): string
    {
        $filtered = array_values(array_filter(array_map('trim', $clauses), fn (string $clause) => $clause !== ''));

        if ($filtered === []) {
            throw new InvalidArgumentException('At least one clause must be provided.');
        }

        if (count($filtered) === 1) {
            return $filtered[0];
        }

        $wrapped = array_map(
            fn (string $clause): string => self::wrapIfNeeded(self::raw($clause)->expression),
            $filtered,
        );

        return implode(sprintf(' %s ', $operator), $wrapped);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if ($name === 'not') {
            return self::negate(...$arguments);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', self::class, $name));
    }

    private function pendingField(string $operator, string $field): PendingField
    {
        return new PendingField(
            trim($field),
            fn (string $clause): self => $this->combine($operator, $clause),
        );
    }

    private function pendingMetadata(string $operator, string $key): PendingField
    {
        return new PendingField(
            self::metadataField($key),
            fn (string $clause): self => $this->combine($operator, $clause),
        );
    }

    private function combine(string $operator, self|string $clause): self
    {
        $right = $clause instanceof self ? $clause->expression : self::raw($clause)->expression;

        $leftWrapped = self::wrapIfNeeded($this->expression);
        $rightWrapped = self::wrapIfNeeded($right);

        return new self(sprintf('%s %s %s', $leftWrapped, $operator, $rightWrapped));
    }

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
            || str_starts_with($upper, '-')
        ) {
            if (! (str_starts_with($trimmed, '(') && str_ends_with($trimmed, ')'))) {
                return '(' . $trimmed . ')';
            }
        }

        return $trimmed;
    }

    /**
     * @internal
     */
    public static function formatValue(mixed $value, string $operator): string
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

    /**
     * @internal
     */
    public static function buildComparison(string $field, string $operator, mixed $value): string
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
     * @internal
     */
    public static function buildExistence(string $field): string
    {
        $field = trim($field);

        if ($field === '') {
            throw new InvalidArgumentException('Field cannot be empty.');
        }

        return sprintf("%s:'*'", $field);
    }

    private static function metadataField(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('Metadata key cannot be empty.');
        }

        $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $key);

        return "metadata['{$escaped}']";
    }
}

final class PendingField
{
    /**
     * @param Closure(string): StripeSearchQuery $completer
     */
    public function __construct(
        private readonly string $field,
        private readonly Closure $completer,
    ) {
        if ($this->field === '') {
            throw new InvalidArgumentException('Field cannot be empty.');
        }
    }

    public function equals(mixed $value): StripeSearchQuery
    {
        return ($this->completer)(StripeSearchQuery::buildComparison($this->field, ':', $value));
    }

    public function greaterThan(mixed $value): StripeSearchQuery
    {
        return ($this->completer)(StripeSearchQuery::buildComparison($this->field, '>', $value));
    }

    public function greaterThanOrEquals(mixed $value): StripeSearchQuery
    {
        return ($this->completer)(StripeSearchQuery::buildComparison($this->field, '>=', $value));
    }

    public function lessThan(mixed $value): StripeSearchQuery
    {
        return ($this->completer)(StripeSearchQuery::buildComparison($this->field, '<', $value));
    }

    public function lessThanOrEquals(mixed $value): StripeSearchQuery
    {
        return ($this->completer)(StripeSearchQuery::buildComparison($this->field, '<=', $value));
    }

    public function exists(): StripeSearchQuery
    {
        return ($this->completer)(StripeSearchQuery::buildExistence($this->field));
    }
}
