<?php

namespace App\Services\Stripe\SearchBuilders;

use App\Services\StripeService;
use InvalidArgumentException;
use Stripe\SearchResult;

abstract class Base
{
    /**
     * @var array<int, array{boolean: string, clause: string}>
     */
    protected array $clauses = [];

    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

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

    public function expand(array|string $fields): static
    {
        $fields = $this->normalizeExpandFields($fields);

        if ($fields === []) {
            return $this;
        }

        $existing = $this->options['expand'] ?? [];
        $this->options['expand'] = array_values(array_unique([...$existing, ...$fields]));

        return $this;
    }

    public function limit(int $limit): static
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Stripe search limits must be between 1 and 100.');
        }

        $this->options['limit'] = $limit;

        return $this;
    }

    public function page(string $cursor): static
    {
        $cursor = trim($cursor);

        if ($cursor === '') {
            throw new InvalidArgumentException('The Stripe search page cursor cannot be empty.');
        }

        $this->options['page'] = $cursor;

        return $this;
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

        return $this->runSearch($query, $this->options);
    }

    abstract protected function runSearch(string $query, array $options): SearchResult;

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

    /**
     * @return array<int, string>
     */
    protected function normalizeExpandFields(array|string $fields): array
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $normalized = [];

        foreach ($fields as $field) {
            $field = trim((string) $field);

            if ($field === '') {
                continue;
            }

            $normalized[] = $field;
        }

        return $normalized;
    }
}
