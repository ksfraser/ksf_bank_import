<?php

namespace Ksfraser\FaBankImport\QueryBuilders;

use Ksfraser\FaBankImport\Specifications\BiTransactionSpecification;

/**
 * BiTransactionQueryBuilder
 * 
 * Fluent query builder for BiTransaction queries.
 * Separates query construction from execution.
 * Enables testable, reusable query logic.
 * 
 * @package Ksfraser\FaBankImport\QueryBuilders
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
 */
final class BiTransactionQueryBuilder
{
    private array $criteria = [];
    private array $sorting = [];
    private int $limitValue = 100;
    private int $offsetValue = 0;

    /**
     * Add WHERE condition
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function where(string $field, string $operator, mixed $value): self
    {
        $this->criteria[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add BETWEEN condition
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function whereBetween(string $field, mixed $min, mixed $max): self
    {
        $this->criteria[] = [
            'field' => $field,
            'operator' => 'BETWEEN',
            'value' => [$min, $max],
        ];

        return $this;
    }

    /**
     * Add IN condition
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function whereIn(string $field, array $values): self
    {
        $this->criteria[] = [
            'field' => $field,
            'operator' => 'IN',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Add IS NULL condition
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function whereIsNull(string $field): self
    {
        $this->criteria[] = [
            'field' => $field,
            'operator' => 'IS NULL',
            'value' => null,
        ];

        return $this;
    }

    /**
     * Add IS NOT NULL condition
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function whereIsNotNull(string $field): self
    {
        $this->criteria[] = [
            'field' => $field,
            'operator' => 'IS NOT NULL',
            'value' => null,
        ];

        return $this;
    }

    /**
     * Apply a specification
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function apply(BiTransactionSpecification $spec): self
    {
        $conditions = $spec->flatten();

        foreach ($conditions as $condition) {
            if ($condition->isLeaf()) {
                $this->criteria[] = [
                    'field' => $condition->getField(),
                    'operator' => $condition->getOperator(),
                    'value' => $condition->getValue(),
                ];
            }
        }

        return $this;
    }

    /**
     * Add ORDER BY
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->sorting[] = [
            'field' => $field,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * Set LIMIT
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;

        return $this;
    }

    /**
     * Set OFFSET
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;

        return $this;
    }

    /**
     * Clear all criteria and sorting
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function reset(): self
    {
        $this->criteria = [];
        $this->sorting = [];
        $this->limitValue = 100;
        $this->offsetValue = 0;

        return $this;
    }

    /**
     * Get accumulated criteria
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * Get accumulated sorting
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getSorting(): array
    {
        return $this->sorting;
    }

    /**
     * Get limit
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLimit(): int
    {
        return $this->limitValue;
    }

    /**
     * Get offset
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOffset(): int
    {
        return $this->offsetValue;
    }

    /**
     * Convert to array for API/serialization
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function toArray(): array
    {
        return [
            'criteria' => $this->criteria,
            'sorting' => $this->sorting,
            'limit' => $this->limitValue,
            'offset' => $this->offsetValue,
        ];
    }

    /**
     * Build a query string (for debugging)
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function toDebugString(): string
    {
        $parts = [];

        foreach ($this->criteria as $cond) {
            $parts[] = "{$cond['field']} {$cond['operator']} ?";
        }

        $query = !empty($parts) ? 'WHERE ' . implode(' AND ', $parts) : '';

        if (!empty($this->sorting)) {
            $orderParts = [];
            foreach ($this->sorting as $sort) {
                $orderParts[] = "{$sort['field']} {$sort['direction']}";
            }
            $query .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        if ($this->limitValue > 0) {
            $query .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue > 0) {
            $query .= " OFFSET {$this->offsetValue}";
        }

        return trim($query);
    }

    /**
     * Check if query has any conditions
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function hasConditions(): bool
    {
        return !empty($this->criteria);
    }

    /**
     * Get number of conditions
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConditionCount(): int
    {
        return count($this->criteria);
    }

    /**
     * Get page number (based on limit/offset)
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPage(): int
    {
        if ($this->limitValue <= 0) {
            return 1;
        }

        return (int)floor($this->offsetValue / $this->limitValue) + 1;
    }

    /**
     * Set pagination by page number
 * @BABOK FR-PROC-004, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function page(int $page, int $pageSize = 50): self
    {
        $this->limitValue = $pageSize;
        $this->offsetValue = ($page - 1) * $pageSize;

        return $this;
    }
}
