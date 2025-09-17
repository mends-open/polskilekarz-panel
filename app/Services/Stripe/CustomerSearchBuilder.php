<?php

namespace App\Services\Stripe;

use Stripe\SearchResult;

class CustomerSearchBuilder extends AbstractSearchBuilder
{
    public function whereMetadata(string|callable $field, ?string $value = null, string $operator = ':'): static
    {
        if (is_callable($field)) {
            return $this->addGroup($field, 'AND');
        }

        $metadataField = $this->service->metadataField($field);

        return $this->addFieldClause($metadataField, $this->assertValue($value, $field), $operator, 'AND');
    }

    public function orWhereMetadata(string|callable $field, ?string $value = null, string $operator = ':'): static
    {
        if (is_callable($field)) {
            return $this->addGroup($field, 'OR');
        }

        $metadataField = $this->service->metadataField($field);

        return $this->addFieldClause($metadataField, $this->assertValue($value, $field), $operator, 'OR');
    }

    protected function runSearch(string $query, array $options): SearchResult
    {
        return $this->service->searchCustomers($query, $options);
    }
}
