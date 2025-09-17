<?php

namespace App\Services\Stripe;

use App\Services\StripeService;
use InvalidArgumentException;
use Stripe\SearchResult;

class CustomerSearchBuilder
{
    private StripeService $service;

    /**
     * @var array<int, array{boolean: string, clause: string}>
     */
    private array $clauses = [];

    public function __construct(StripeService $service)
    {
        $this->service = $service;
    }

    public function where(string $field, string $value, string $operator = ':'): self
    {
        return $this->addFieldClause($field, $value, $operator, 'AND');
    }

    public function orWhere(string $field, string $value, string $operator = ':'): self
    {
        return $this->addFieldClause($field, $value, $operator, 'OR');
    }

    public function whereMetadata(string $field, string $value, string $operator = ':'): self
    {
        $metadataField = $this->service->metadataField($field);

        return $this->addFieldClause($metadataField, $value, $operator, 'AND');
    }

    public function orWhereMetadata(string $field, string $value, string $operator = ':'): self
    {
        $metadataField = $this->service->metadataField($field);

        return $this->addFieldClause($metadataField, $value, $operator, 'OR');
    }

    /**
     * @param callable(self):void $callback
     */
    public function whereGroup(callable $callback): self
    {
        return $this->addGroup($callback, 'AND');
    }

    /**
     * @param callable(self):void $callback
     */
    public function orWhereGroup(callable $callback): self
    {
        return $this->addGroup($callback, 'OR');
    }

    public function whereRaw(string $clause): self
    {
        return $this->addClause($clause, 'AND');
    }

    public function orWhereRaw(string $clause): self
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
            throw new InvalidArgumentException('Cannot execute a Stripe customer search without any conditions.');
        }

        return $this->service->searchCustomers($query);
    }

    private function addFieldClause(string $field, string $value, string $operator, string $boolean): self
    {
        $clause = $this->service->buildQueryClause($field, $value, $operator);

        return $this->addClause($clause, $boolean);
    }

    private function addGroup(callable $callback, string $boolean): self
    {
        $groupBuilder = new self($this->service);
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

    private function addClause(string $clause, string $boolean): self
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

    private function normalizeBoolean(string $boolean): string
    {
        return strtoupper($boolean) === 'OR' ? 'OR' : 'AND';
    }
}
