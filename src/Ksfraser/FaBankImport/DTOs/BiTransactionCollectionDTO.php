<?php

namespace Ksfraser\FaBankImport\DTOs;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * BiTransactionCollectionDTO
 *
 * Collection of BiTransactionDTO objects.
 * Provides iterator, countable interface and utility methods for working with multiple DTOs.
 * Useful for paginated results and bulk operations.
 *
 * @package Ksfraser\FaBankImport\DTOs
 * @implements IteratorAggregate, Countable
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
 */
final class BiTransactionCollectionDTO implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var BiTransactionDTO[]
     */
    private $items = [];

    /**
     * Access item by index (ArrayAccess-like, without implementing interface)
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function get(int $index): ?BiTransactionDTO
    {
        return $this->items[$index] ?? null;
    }

    /**
     * ArrayAccess: item exists at offset
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * ArrayAccess: item at offset
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * ArrayAccess: read-only collection, offsets cannot be set
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('BiTransactionCollectionDTO is read-only');
    }

    /**
     * ArrayAccess: read-only collection, offsets cannot be unset
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('BiTransactionCollectionDTO is read-only');
    }

    /**
     * Private constructor - enforces factory method usage
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    private function __construct()
    {
    }

    /**
     * Create collection from array of BiTransactionDTO objects
     * 
     * @param BiTransactionDTO[] $items
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public static function fromArray(array $items): self
    {
        $collection = new self();
        $collection->items = array_values($items); // Reindex array
        return $collection;
    }

    /**
     * Get iterator for collection
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count items in collection
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get all items as array
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Filter collection by predicate callback
     * 
     * @param callable(BiTransactionDTO): bool $predicate
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function filter(callable $predicate): self
    {
        $filtered = array_filter($this->items, $predicate);
        return self::fromArray($filtered);
    }

    /**
     * Map collection using callback
     * 
     * @param callable(BiTransactionDTO): mixed $callback
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * Find first item matching predicate
     * 
     * @param callable(BiTransactionDTO): bool $predicate
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function findFirst(callable $predicate): ?BiTransactionDTO
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Check if collection is empty
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get first item
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function first(): ?BiTransactionDTO
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get last item
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function last(): ?BiTransactionDTO
    {
        $count = count($this->items);
        return $count > 0 ? $this->items[$count - 1] : null;
    }

    /**
     * Serialize collection to array of arrays
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function toArray(): array
    {
        return array_map(function (BiTransactionDTO $dto) { return $dto->toArray(); }, $this->items);
    }

    /**
     * Serialize collection to JSON
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Reduce collection to single value
     * 
     * @param callable(mixed, BiTransactionDTO): mixed $callback
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Check if any item matches predicate
     * 
     * @param callable(BiTransactionDTO): bool $predicate
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
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
     * @param callable(BiTransactionDTO): bool $predicate
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function every(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get collection of matched transactions
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMatched(): self
    {
        return $this->filter(function (BiTransactionDTO $dto) { return $dto->isMatched(); });
    }

    /**
     * Get collection of unmatched transactions
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getUnmatched(): self
    {
        return $this->filter(function (BiTransactionDTO $dto) { return !$dto->isMatched(); });
    }

    /**
     * Get collection by transaction DC (Debit/Credit)
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getByDC(string $dc): self
    {
        return $this->filter(function (BiTransactionDTO $dto) { return $dto->getTransactionDC() === $dc; });
    }

    /**
     * Get debit transactions
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDebits(): self
    {
        return $this->getByDC('D');
    }

    /**
     * Get credit transactions
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getCredits(): self
    {
        return $this->getByDC('C');
    }

    /**
     * Sum all transaction amounts
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function sumAmounts(): float
    {
        return (float)array_reduce(
            $this->items,
            function (float $carry, BiTransactionDTO $dto) { return $carry + $dto->getTransactionAmount(); },
            0.0
        );
    }

    /**
     * Group collection by field value
     * 
     * @return array<string, BiTransactionCollectionDTO>
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function groupBy(callable $keyExtractor): array
    {
        $groups = [];
        foreach ($this->items as $item) {
            $key = (string)$keyExtractor($item);
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $item;
        }

        return array_map(function (array $items) { return self::fromArray($items); }, $groups);
    }
}
