<?php

namespace Ksfraser\FaBankImport\DTOs;

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
 */
final class BiTransactionCollectionDTO implements IteratorAggregate, Countable
{
    /**
     * @var BiTransactionDTO[]
     */
    private array $items = [];

    /**
     * Private constructor - enforces factory method usage
     */
    private function __construct()
    {
    }

    /**
     * Create collection from array of BiTransactionDTO objects
     * 
     * @param BiTransactionDTO[] $items
     */
    public static function fromArray(array $items): self
    {
        $collection = new self();
        $collection->items = array_values($items); // Reindex array
        return $collection;
    }

    /**
     * Get iterator for collection
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count items in collection
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Access item by index (ArrayAccess-like, without implementing interface)
     */
    public function get(int $index): ?BiTransactionDTO
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Magic method for array access syntax
     */
    public function __get(int $index): ?BiTransactionDTO
    {
        return $this->get($index);
    }

    /**
     * Get all items as array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Filter collection by predicate callback
     * 
     * @param callable(BiTransactionDTO): bool $predicate
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
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * Find first item matching predicate
     * 
     * @param callable(BiTransactionDTO): bool $predicate
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
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get first item
     */
    public function first(): ?BiTransactionDTO
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get last item
     */
    public function last(): ?BiTransactionDTO
    {
        $count = count($this->items);
        return $count > 0 ? $this->items[$count - 1] : null;
    }

    /**
     * Serialize collection to array of arrays
     */
    public function toArray(): array
    {
        return array_map(fn(BiTransactionDTO $dto) => $dto->toArray(), $this->items);
    }

    /**
     * Serialize collection to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Reduce collection to single value
     * 
     * @param callable(mixed, BiTransactionDTO): mixed $callback
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Check if any item matches predicate
     * 
     * @param callable(BiTransactionDTO): bool $predicate
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
     * Get collection of matched transactions
     */
    public function getMatched(): self
    {
        return $this->filter(fn(BiTransactionDTO $dto) => $dto->isMatched());
    }

    /**
     * Get collection of unmatched transactions
     */
    public function getUnmatched(): self
    {
        return $this->filter(fn(BiTransactionDTO $dto) => !$dto->isMatched());
    }

    /**
     * Get collection by transaction DC (Debit/Credit)
     */
    public function getByDC(string $dc): self
    {
        return $this->filter(fn(BiTransactionDTO $dto) => $dto->getTransactionDC() === $dc);
    }

    /**
     * Get debit transactions
     */
    public function getDebits(): self
    {
        return $this->getByDC('D');
    }

    /**
     * Get credit transactions
     */
    public function getCredits(): self
    {
        return $this->getByDC('C');
    }

    /**
     * Sum all transaction amounts
     */
    public function sumAmounts(): float
    {
        return (float)array_reduce(
            $this->items,
            fn(float $carry, BiTransactionDTO $dto) => $carry + $dto->getTransactionAmount(),
            0.0
        );
    }

    /**
     * Group collection by field value
     * 
     * @return array<string, BiTransactionCollectionDTO>
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

        return array_map(fn(array $items) => self::fromArray($items), $groups);
    }
}
