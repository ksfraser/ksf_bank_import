<?php

namespace Ksfraser\FaBankImport\DTOs;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * Collection of BiLineItemDTO instances with functional operations
 *
 * Provides collection operations like filter, map, reduce, groupBy, etc.
 * Implements Countable and IteratorAggregate for PHP standard library compatibility.
 *
 * @since 2025-01-15
 */
final class BiLineItemCollectionDTO implements Countable, IteratorAggregate
{
    /** @var BiLineItemDTO[] */
    private array $items = [];

    /**
     * Add a DTO to the collection
     *
     * @param BiLineItemDTO $dto DTO to add
     * @return void
     */
    public function add(BiLineItemDTO $dto): void
    {
        $this->items[] = $dto;
    }

    /**
     * Get count of items in collection
     *
     * @return int Number of items
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get iterator for collection
     *
     * @return ArrayIterator Iterator over items
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Filter collection by predicate
     *
     * Returns new collection containing only items matching the predicate.
     *
     * @param callable $predicate Function(BiLineItemDTO): bool
     * @return self New filtered collection
     */
    public function filter(callable $predicate): self
    {
        $filtered = new self();
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                $filtered->add($item);
            }
        }
        return $filtered;
    }

    /**
     * Map over collection items
     *
     * Returns new array with results of applying callback to each item.
     *
     * @param callable $callback Function(BiLineItemDTO): mixed
     * @return array Array of mapped values
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * Reduce collection to single value
     *
     * @param callable $callback Function($carry, BiLineItemDTO): mixed
     * @param mixed $initial Initial accumulator value
     * @return mixed Final accumulated value
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Check if any item matches predicate
     *
     * @param callable $predicate Function(BiLineItemDTO): bool
     * @return bool True if at least one item matches
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all items match predicate
     *
     * @param callable $predicate Function(BiLineItemDTO): bool
     * @return bool True if all items match
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get only matched items
     *
     * @return self New collection with only matched items
     */
    public function getMatched(): self
    {
        return $this->filter(fn(BiLineItemDTO $dto) => $dto->isMatched());
    }

    /**
     * Get only unmatched items
     *
     * @return self New collection with only unmatched items
     */
    public function getUnmatched(): self
    {
        return $this->filter(fn(BiLineItemDTO $dto) => !$dto->isMatched());
    }

    /**
     * Calculate sum of all amounts
     *
     * @return float Total of all amounts
     */
    public function sumAmounts(): float
    {
        return $this->reduce(fn($carry, BiLineItemDTO $dto) => $carry + $dto->getAmount(), 0.00);
    }

    /**
     * Group items by a key derived from the callback
     *
     * @param callable $keyCallback Function(BiLineItemDTO): string|int
     * @return array Grouped items: [key => [item1, item2, ...]]
     */
    public function groupBy(callable $keyCallback): array
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $key = $keyCallback($item);
            if (!isset($grouped[$key])) {
                $grouped[$key] = new self();
            }
            $grouped[$key]->add($item);
        }
        return $grouped;
    }

    /**
     * Convert all items to array
     *
     * @return array Array of DTOs as arrays
     */
    public function toArray(): array
    {
        return array_map(fn(BiLineItemDTO $dto) => $dto->toArray(), $this->items);
    }

    /**
     * Convert all items to JSON
     *
     * @return string JSON representation of collection
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Get all items as array
     *
     * @return BiLineItemDTO[] All items in collection
     */
    public function getAll(): array
    {
        return $this->items;
    }

    /**
     * Check if collection is empty
     *
     * @return bool True if no items
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * Get first item or null
     *
     * @return BiLineItemDTO|null First item or null if empty
     */
    public function first(): ?BiLineItemDTO
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get last item or null
     *
     * @return BiLineItemDTO|null Last item or null if empty
     */
    public function last(): ?BiLineItemDTO
    {
        return empty($this->items) ? null : end($this->items);
    }
}
