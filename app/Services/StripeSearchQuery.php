<?php

declare(strict_types=1);

namespace App\Services;

use BadMethodCallException;
use DateTimeInterface;
use InvalidArgumentException;

final class StripeSearchQuery
{
    private ?string $expression;

    public function __construct(?string $clause = null)
    {
        $this->expression = $clause !== null ? $this->sanitizeClause($clause) : null;
    }

    /**
     * Start a clause for the provided field.
     */
    public function field(string $field): PendingField
    {
        return $this->pendingField(null, $field);
    }

    /**
     * Start a clause for the provided metadata key.
     */
    public function metadata(string $key): PendingField
    {
        return $this->pendingMetadata(null, $key);
    }

    /**
     * Append a raw clause to the query.
     */
    public function raw(string $clause): self
    {
        return $this->appendClause($clause);
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
     *
     * @param callable(self): (self|string|void)|string $builder
     */
    public function andGroup(callable|string $builder): self
    {
        return $this->and($this->group($builder));
    }

    /**
     * Combine the current query with a grouped clause using OR.
     *
     * @param callable(self): (self|string|void)|string $builder
     */
    public function orGroup(callable|string $builder): self
    {
        return $this->or($this->group($builder));
    }

    /**
     * Negate the current clause using Stripe's unary minus.
     */
    public function not(): self
    {
        $this->expression = '-' . $this->wrapIfNeeded($this->requireExpression());

        return $this;
    }

    /**
     * Wrap the current clause in parentheses.
     */
    public function grouped(): self
    {
        $this->expression = '(' . $this->requireExpression() . ')';

        return $this;
    }

    /**
     * Build a grouped clause using the provided callback or raw clause.
     *
     * @param callable(self): (self|string|void)|string $builder
     */
    public function group(callable|string $builder): string
    {
        if (is_callable($builder)) {
            $nested = new self();
            $result = $builder($nested);

            if ($result instanceof self) {
                $nested = $result;
            } elseif (is_string($result)) {
                $nested->raw($result);
            }

            $clause = $nested->expression;
        } else {
            $clause = $this->sanitizeClause($builder);
        }

        if ($clause === null || $clause === '') {
            throw new InvalidArgumentException('Group must contain at least one clause.');
        }

        return '(' . $this->sanitizeClause($clause) . ')';
    }

    /**
     * Represent the query as a string.
     */
    public function toString(): string
    {
        return $this->requireExpression();
    }

    public function __toString(): string
    {
        return $this->expression ?? '';
    }

    /**
     * @internal
     */
    public function appendClause(string $clause, ?string $boolean = null): self
    {
        return $this->applyClause($clause, $boolean);
    }

    /**
     * @internal
     */
    public function buildComparison(string $field, string $operator, mixed $value): string
    {
        $operator = trim($operator);

        if (! in_array($operator, [':', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Unsupported operator provided.');
        }

        $field = $this->sanitizeField($field);

        return sprintf('%s%s%s', $field, $operator, $this->formatValue($value, $operator));
    }

    /**
     * @internal
     */
    public function buildExistence(string $field): string
    {
        $field = $this->sanitizeField($field);

        return sprintf("%s:'*'", $field);
    }

    private function combine(string $operator, self|string $clause): self
    {
        $clauseString = $clause instanceof self
            ? $clause->requireExpression()
            : $this->sanitizeClause($clause);

        return $this->applyClause($clauseString, $operator);
    }

    private function applyClause(string $clause, ?string $boolean): self
    {
        $clause = $this->sanitizeClause($clause);

        if ($this->expression === null) {
            $this->expression = $clause;

            return $this;
        }

        $operator = $boolean ?? 'AND';

        $this->expression = sprintf(
            '%s %s %s',
            $this->wrapIfNeeded($this->expression),
            $operator,
            $this->wrapIfNeeded($clause),
        );

        return $this;
    }

    private function pendingField(?string $operator, string $field): PendingField
    {
        return new PendingField(
            $this,
            $this->sanitizeField($field),
            $operator,
        );
    }

    private function pendingMetadata(?string $operator, string $key): PendingField
    {
        return new PendingField(
            $this,
            $this->metadataField($key),
            $operator,
        );
    }

    private function requireExpression(): string
    {
        if ($this->expression === null || $this->expression === '') {
            throw new BadMethodCallException('No clause has been added to the query.');
        }

        return $this->expression;
    }

    private function sanitizeClause(string $clause): string
    {
        $trimmed = trim($clause);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Clause cannot be empty.');
        }

        return $trimmed;
    }

    private function sanitizeField(string $field): string
    {
        $field = trim($field);

        if ($field === '') {
            throw new InvalidArgumentException('Field cannot be empty.');
        }

        return $field;
    }

    private function wrapIfNeeded(string $clause): string
    {
        $trimmed = $this->sanitizeClause($clause);
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

    private function formatValue(mixed $value, string $operator): string
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

    private function metadataField(string $key): string
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
    public function __construct(
        private readonly StripeSearchQuery $query,
        private readonly string $field,
        private readonly ?string $boolean,
    ) {
    }

    public function equals(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, ':', $value));
    }

    public function greaterThan(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '>', $value));
    }

    public function greaterThanOrEquals(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '>=', $value));
    }

    public function lessThan(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '<', $value));
    }

    public function lessThanOrEquals(mixed $value): StripeSearchQuery
    {
        return $this->complete($this->query->buildComparison($this->field, '<=', $value));
    }

    public function exists(): StripeSearchQuery
    {
        return $this->complete($this->query->buildExistence($this->field));
    }

    private function complete(string $clause): StripeSearchQuery
    {
        return $this->query->appendClause($clause, $this->boolean);
    }
}
