<?php

namespace App\Services\Stripe;

use InvalidArgumentException;
use Stripe\SearchResult;

abstract class AbstractSearchBuilder
{
    /**
     * @var array<int, array{boolean: string, clause: string}>
     */
    protected array $clauses = [];

    public function __construct(protected readonly StripeService $service)
    {
    }

    public function where(string|callable $field, ?string $value = null, string $operator = ':'): static
    {
        if (is_callable($field)) {
            return $this->addGroup($field, 'AND');
        }

        return $this->addFieldClause($field, $this->assertValue($value, $field), $operator, 'AND');
    }

    public function orWhere(string|callable $field, ?string $value = null, string $operator = ':'): static
    {
        if (is_callable($field)) {
            return $this->addGroup($field, 'OR');
        }

        return $this->addFieldClause($field, $this->assertValue($value, $field), $operator, 'OR');
    }

    public function whereGroup(callable $callback): static
    {
        return $this->addGroup($callback, 'AND');
    }

    public function orWhereGroup(callable $callback): static
    {
        return $this->addGroup($callback, 'OR');
    }

    public function whereRaw(string $clause): static
    {
        return $this->addClause($clause, 'AND');
    }

    public function orWhereRaw(string $clause): static
    {
        return $this->addClause($clause, 'OR');
    }

    public function toQueryString(): string
    {
        if ($this->clauses === []) {
            return '';
        }

        $parts = [];

        foreach ($this->clauses as $index => $clause) {
            if ($index === 0) {
                $parts[] = $clause['clause'];

                continue;
            }

            $parts[] = $clause['boolean'] . ' ' . $clause['clause'];
        }

        return implode(' ', $parts);
    }

    public function get(): SearchResult
    {
        $query = $this->toQueryString();

        if ($query === '') {
            throw new InvalidArgumentException('Cannot execute a Stripe search without any conditions.');
        }

        return $this->runSearch($query);
    }

    abstract protected function runSearch(string $query): SearchResult;

    protected function addFieldClause(string $field, string $value, string $operator, string $boolean): static
    {
        $clause = $this->service->buildQueryClause($field, $value, $operator);

        return $this->addClause($clause, $boolean);
    }

    protected function addGroup(callable $callback, string $boolean): static
    {
        $groupBuilder = $this->newInstance();
        $callback($groupBuilder);

        $groupQuery = $groupBuilder->toQueryString();

        if ($groupQuery !== '') {
            $this->clauses[] = [
                'boolean' => $this->normalizeBoolean($boolean),
                'clause' => '(' . $groupQuery . ')',
            ];
        }

        return $this;
    }

    protected function assertValue(?string $value, string $field): string
    {
        if ($value === null) {
            throw new InvalidArgumentException("A value must be provided when filtering by '{$field}'.");
        }

        return $value;
    }

    protected function addClause(string $clause, string $boolean): static
    {
        $clause = trim($clause);

        if ($clause === '') {
            return $this;
        }

        $this->clauses[] = [
            'boolean' => $this->normalizeBoolean($boolean),
            'clause' => $clause,
        ];

        return $this;
    }

    protected function normalizeBoolean(string $boolean): string
    {
        return strtoupper($boolean) === 'OR' ? 'OR' : 'AND';
    }

    protected function newInstance(): static
    {
        return new static($this->service);
    }
}
