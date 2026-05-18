<?php

namespace Ksfraser\FaBankImport\Specifications;

/**
 * BiTransactionSpecification
 * 
 * Implements the Specification pattern for building complex queries.
 * Provides fluent interface for combining multiple conditions without SQL.
 * Enables testable, reusable query logic separated from persistence layer.
 * 
 * @package Ksfraser\FaBankImport\Specifications
 */
final class BiTransactionSpecification
{
    /**
     * Specification criteria structure
     * [
     *     'type' => 'and'|'or'|'condition',
     *     'left' => BiTransactionSpecification|null,
     *     'operator' => 'and'|'or'|null,
     *     'right' => BiTransactionSpecification|null,
     *     'field' => string,
     *     'operator' => string ('=', '>', '<', '>=', '<=', '!=', 'IN', 'BETWEEN', 'IS NULL', 'IS NOT NULL'),
     *     'value' => mixed,
     * ]
     */
    private array $spec = [];

    private function __construct(array $spec = [])
    {
        $this->spec = $spec;
    }

    /**
     * Create condition: field = value
     */
    public static function where(string $field, string $operator, mixed $value): self
    {
        return new self([
            'type' => 'condition',
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ]);
    }

    /**
     * Create BETWEEN condition
     */
    public static function whereBetween(string $field, mixed $min, mixed $max): self
    {
        return new self([
            'type' => 'condition',
            'field' => $field,
            'operator' => 'BETWEEN',
            'value' => [$min, $max],
        ]);
    }

    /**
     * Create IN condition
     */
    public static function whereIn(string $field, array $values): self
    {
        return new self([
            'type' => 'condition',
            'field' => $field,
            'operator' => 'IN',
            'value' => $values,
        ]);
    }

    /**
     * Create IS NULL condition
     */
    public static function whereIsNull(string $field): self
    {
        return new self([
            'type' => 'condition',
            'field' => $field,
            'operator' => 'IS NULL',
            'value' => null,
        ]);
    }

    /**
     * Create IS NOT NULL condition
     */
    public static function whereIsNotNull(string $field): self
    {
        return new self([
            'type' => 'condition',
            'field' => $field,
            'operator' => 'IS NOT NULL',
            'value' => null,
        ]);
    }

    /**
     * Create matched specification
     */
    public static function matched(): self
    {
        return self::where('matched', '=', true);
    }

    /**
     * Create unmatched specification
     */
    public static function unmatched(): self
    {
        return self::where('matched', '=', false);
    }

    /**
     * Create debit specification
     */
    public static function debit(): self
    {
        return self::where('transactionDC', '=', 'D');
    }

    /**
     * Create credit specification
     */
    public static function credit(): self
    {
        return self::where('transactionDC', '=', 'C');
    }

    /**
     * Combine with AND logic
     */
    public function and(self $other): self
    {
        return new self([
            'type' => 'composite',
            'left' => $this,
            'operator' => 'AND',
            'right' => $other,
        ]);
    }

    /**
     * Combine with OR logic
     */
    public function or(self $other): self
    {
        return new self([
            'type' => 'composite',
            'left' => $this,
            'operator' => 'OR',
            'right' => $other,
        ]);
    }

    /**
     * Get specification criteria
     */
    public function getCriteria(): array
    {
        return $this->spec;
    }

    /**
     * Convert to array for persistence/serialization
     */
    public function toArray(): array
    {
        if ($this->spec['type'] === 'composite') {
            return [
                'type' => 'composite',
                'operator' => $this->spec['operator'],
                'left' => $this->spec['left']->toArray(),
                'right' => $this->spec['right']->toArray(),
            ];
        }

        return $this->spec;
    }

    /**
     * Check if specification is a leaf condition (not composite)
     */
    public function isLeaf(): bool
    {
        return $this->spec['type'] === 'condition';
    }

    /**
     * Get the field being checked (if leaf condition)
     */
    public function getField(): ?string
    {
        return $this->spec['field'] ?? null;
    }

    /**
     * Get the operator (if leaf condition)
     */
    public function getOperator(): ?string
    {
        return $this->spec['operator'] ?? null;
    }

    /**
     * Get the value being checked (if leaf condition)
     */
    public function getValue(): mixed
    {
        return $this->spec['value'] ?? null;
    }

    /**
     * Flatten composite specification to array of leaf conditions
     */
    public function flatten(): array
    {
        if ($this->isLeaf()) {
            return [$this];
        }

        $left = $this->spec['left']->flatten();
        $right = $this->spec['right']->flatten();

        return array_merge($left, $right);
    }
}
