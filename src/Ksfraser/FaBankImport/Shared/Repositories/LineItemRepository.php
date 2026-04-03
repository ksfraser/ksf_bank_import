<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Shared\Repositories;

use PDO;
use PDOStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiLineItem;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidRepositoryStateException;
use Ksfraser\FaBankImport\Shared\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\LineItemNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\RepositoryException;

/**
 * LineItemRepository Implementation
 *
 * Handles persistence and retrieval of BiLineItem entities from the database.
 * Implements the LineItemRepositoryInterface contract with full CRUD operations
 * and query filtering.
 *
 * Supports configurable table prefix via PrefixedRepositoryInterface for
 * portability across different database schemas and FA instances.
 *
 * @package Ksfraser\FaBankImport\Shared\Repositories
 */
final class LineItemRepository implements LineItemRepositoryInterface, PrefixedRepositoryInterface
{
    /**
     * @var PDO Database connection
     */
    private PDO $pdo;

    /**
     * @var string Table prefix (e.g., '0_' for FrontAccounting)
     */
    private string $prefix = '0_';

    /**
     * @var string Base table name without prefix
     */
    private string $tableName = 'bi_line_items';

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     * @param string|null $prefix Optional table prefix (defaults to '0_')
     */
    public function __construct(PDO $pdo, ?string $prefix = null)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($prefix !== null) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Set the table prefix used for all queries in this repository.
     *
     * @param string $prefix The table prefix
     * @return void
     */
    public function setTablePrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Get the currently configured table prefix.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the base table name (without prefix).
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get the fully qualified table name (prefix + base name).
     *
     * @return string
     */
    public function getFullTableName(): string
    {
        return $this->prefix . $this->tableName;
    }

    /**
     * Helper to get table name for queries.
     *
     * @return string
     */
    private function table(): string
    {
        return $this->getFullTableName();
    }

    /**
     * Find line item by ID
     *
     * @param int $id Line item ID
     * @return BiLineItem
     * @throws LineItemNotFoundException If line item not found
     * @throws RepositoryException If database error occurs
     */
    public function findById(int $id): BiLineItem
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table()} WHERE id = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                throw LineItemNotFoundException::byId($id);
            }

            return $this->entityFromRow($row);
        } catch (EntityNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding line item by ID: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find all line items for a transaction
     *
     * @param int $transactionId Transaction ID
     * @return BiLineItem[]
     * @throws RepositoryException If database error occurs
     */
    public function findByTransactionId(int $transactionId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table()} WHERE bi_transaction_id = :transactionId ORDER BY id ASC"
            );
            $stmt->execute(['transactionId' => $transactionId]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->entitiesToArray($rows);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding line items by transaction ID: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find line items by GL account
     *
     * @param int $glAccount GL account number
     * @return BiLineItem[]
     * @throws RepositoryException If database error occurs
     */
    public function findByGLAccount(int $glAccount): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table()} WHERE fa_gl_account = :glAccount ORDER BY id ASC"
            );
            $stmt->execute(['glAccount' => $glAccount]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->entitiesToArray($rows);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding line items by GL account: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Save line item (insert or update)
     *
     * @param BiLineItem $lineItem Line item to save
     * @return int ID of saved line item
     * @throws InvalidRepositoryStateException If line item state invalid
     * @throws LineItemNotFoundException If line item not found for update
     * @throws RepositoryException If database error occurs
     */
    public function save(BiLineItem $lineItem): void
    {
        try {
            $data = $lineItem->toDatabase();

            // Check if insert or update
            if (!empty($data['id'])) {
                $this->update($lineItem);
                return;
            }

            // Remove ID for insert
            unset($data['id']);

            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ":{$col}", $columns);

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->table(),
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
        } catch (InvalidRepositoryStateException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error saving line item: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Update a line item entity
     *
     * @param BiLineItem $lineItem Line item to update
     * @return void
     * @throws InvalidRepositoryStateException If line item state invalid
     * @throws LineItemNotFoundException If line item not found
     * @throws RepositoryException If database error occurs
     */
    private function update(BiLineItem $lineItem): void
    {
        try {
            $data = $lineItem->toDatabase();

            if (empty($data['id'])) {
                throw new InvalidRepositoryStateException('Cannot update line item without ID');
            }

            $id = $data['id'];
            unset($data['id']);

            $sets = array_map(fn($col) => "{$col} = :{$col}", array_keys($data));

            $sql = sprintf(
                "UPDATE %s SET %s WHERE id = :id",
                $this->table(),
                implode(', ', $sets)
            );

            $data['id'] = $id;

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($data);

            if ($stmt->rowCount() === 0) {
                throw LineItemNotFoundException::byId($id);
            }
        } catch (InvalidRepositoryStateException | EntityNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error updating line item: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Delete a line item by ID
     *
     * @param int $id Line item ID
     * @return void
     * @throws RepositoryException If database error occurs
     */
    public function delete(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table()} WHERE id = :id");
            $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error deleting line item: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get total count of line items
     *
     * @return int Total line item count
     * @throws RepositoryException If database error occurs
     */
    public function count(): int
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM {$this->table()}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total'] ?? 0);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error counting line items: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get total amount from all line items
     *
     * @return float Total amount across all line items
     * @throws RepositoryException If database error occurs
     */
    public function getTotalAmount(): float
    {
        try {
            $stmt = $this->pdo->query("SELECT SUM(amount) as total FROM {$this->table()}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total'] ?? 0.0);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error calculating total amount: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get total amount for a transaction
     *
     * @param int $transactionId Transaction ID
     * @return float Total amount for transaction
     * @throws RepositoryException If database error occurs
     */
    public function getTotalAmountForTransaction(int $transactionId): float
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT SUM(amount) as total FROM {$this->table()} WHERE bi_transaction_id = :transactionId"
            );
            $stmt->execute(['transactionId' => $transactionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total'] ?? 0.0);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error calculating transaction total: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Convert database row to BiLineItem entity
     *
     * @param array $row Database row
     * @return BiLineItem
     */
    private function entityFromRow(array $row): BiLineItem
    {
        return BiLineItem::fromDatabase($row);
    }

    /**
     * Convert multiple database rows to BiLineItem entities
     *
     * @param array $rows Database rows
     * @return BiLineItem[]
     */
    private function entitiesToArray(array $rows): array
    {
        return array_map(
            fn(array $row) => $this->entityFromRow($row),
            $rows
        );
    }
}
