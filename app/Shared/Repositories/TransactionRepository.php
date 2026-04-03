<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Shared\Repositories;

use PDO;
use PDOStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidTransactionException;
use Ksfraser\FaBankImport\Shared\Exceptions\TransactionNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\RepositoryException;

/**
 * TransactionRepository Implementation
 *
 * Handles persistence and retrieval of BiTransaction entities from the database.
 * Implements the TransactionRepositoryInterface contract with full CRUD operations,
 * query filtering, and bulk operations.
 *
 * Supports configurable table prefix via PrefixedRepositoryInterface for
 * portability across different database schemas and FA instances.
 *
 * @package Ksfraser\FaBankImport\Shared\Repositories
 */
final class TransactionRepository implements TransactionRepositoryInterface, PrefixedRepositoryInterface
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
    private string $tableName = 'bi_transactions';

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
     * Find a transaction by ID
     *
     * @param int $id Transaction ID
     * @return BiTransaction
     * @throws TransactionNotFoundException If transaction not found
     * @throws RepositoryException If database error occurs
     */
    public function findById(int $id): BiTransaction
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table()} WHERE id = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                throw new TransactionNotFoundException(
                    "Transaction with ID {$id} not found"
                );
            }

            return $this->entityFromRow($row);
        } catch (TransactionNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transaction by ID: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find transactions by FIT ID
     *
     * @param string $fitId FIT transaction ID
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findByFitId(string $fitId, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE fitId = :fitId";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['fitId' => $fitId, 'limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transactions by FIT ID: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find transactions by Statement ID
     *
     * @param int $statementId Statement ID
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findByStatementId(int $statementId, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE smtId = :statementId";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['statementId' => $statementId, 'limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transactions by Statement ID: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find transactions by transaction code
     *
     * @param string $code Transaction code
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findByCode(string $code, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE code = :code";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['code' => $code, 'limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transactions by code: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find transactions by status
     *
     * @param string $status Transaction status
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findByStatus(string $status, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE status = :status";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['status' => $status, 'limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transactions by status: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Save a transaction entity
     *
     * @param BiTransaction $transaction Transaction to save
     * @return int ID of saved transaction
     * @throws InvalidTransactionException If transaction invalid
     * @throws RepositoryException If database error occurs
     */
    public function save(BiTransaction $transaction): int
    {
        try {
            $data = $transaction->toDatabase();

            // Check if insert or update
            if (!empty($data['id'])) {
                return $this->update($transaction);
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

            return (int)$this->pdo->lastInsertId();
        } catch (InvalidTransactionException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error saving transaction: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Update a transaction entity
     *
     * @param BiTransaction $transaction Transaction to update
     * @return int ID of updated transaction
     * @throws InvalidTransactionException If transaction invalid
     * @throws TransactionNotFoundException If transaction not found
     * @throws RepositoryException If database error occurs
     */
    public function update(BiTransaction $transaction): int
    {
        try {
            $data = $transaction->toDatabase();

            if (empty($data['id'])) {
                throw new InvalidTransactionException('Cannot update transaction without ID');
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
                throw new TransactionNotFoundException(
                    "Transaction with ID {$id} not found for update"
                );
            }

            return $id;
        } catch (InvalidTransactionException | TransactionNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error updating transaction: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Delete a transaction by ID
     *
     * @param int $id Transaction ID
     * @return bool True if deleted, false if not found
     * @throws RepositoryException If database error occurs
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table()} WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error deleting transaction: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get total count of transactions
     *
     * @return int Total transaction count
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
                "Error counting transactions: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Bulk insert multiple transactions
     *
     * @param BiTransaction[] $transactions Transactions to insert
     * @return int[] IDs of inserted transactions
     * @throws InvalidTransactionException If any transaction invalid
     * @throws RepositoryException If database error occurs
     */
    public function bulkInsert(array $transactions): array
    {
        try {
            $ids = [];
            $this->pdo->beginTransaction();

            foreach ($transactions as $transaction) {
                $ids[] = $this->save($transaction);
            }

            $this->pdo->commit();
            return $ids;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RepositoryException(
                "Error bulk inserting transactions: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Bulk update multiple transactions
     *
     * @param BiTransaction[] $transactions Transactions to update
     * @return int Number of updated transactions
     * @throws InvalidTransactionException If any transaction invalid
     * @throws RepositoryException If database error occurs
     */
    public function bulkUpdate(array $transactions): int
    {
        try {
            $count = 0;
            $this->pdo->beginTransaction();

            foreach ($transactions as $transaction) {
                $this->update($transaction);
                $count++;
            }

            $this->pdo->commit();
            return $count;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RepositoryException(
                "Error bulk updating transactions: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Bulk delete transactions by IDs
     *
     * @param int[] $ids Transaction IDs to delete
     * @return int Number of deleted transactions
     * @throws RepositoryException If database error occurs
     */
    public function bulkDelete(array $ids): int
    {
        try {
            if (empty($ids)) {
                return 0;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM {$this->table()} WHERE id IN ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($ids);

            return $stmt->rowCount();
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error bulk deleting transactions: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find unmatched transactions
     *
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findUnmatched(?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE matched = 0";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding unmatched transactions: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find transactions by amount range
     *
     * @param float $minAmount Minimum amount
     * @param float $maxAmount Maximum amount
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findByAmountRange(float $minAmount, float $maxAmount, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE amount >= :minAmount AND amount <= :maxAmount";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams([
                'minAmount' => $minAmount,
                'maxAmount' => $maxAmount,
                'limit' => $limit,
                'offset' => $offset
            ]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transactions by amount: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find transactions by date range
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiTransaction[]
     * @throws RepositoryException If database error occurs
     */
    public function findByDateRange(string $startDate, string $endDate, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table()} WHERE created >= :startDate AND created <= :endDate";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'limit' => $limit,
                'offset' => $offset
            ]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding transactions by date: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Convert database row to BiTransaction entity
     *
     * @param array $row Database row
     * @return BiTransaction
     */
    private function entityFromRow(array $row): BiTransaction
    {
        return BiTransaction::fromDatabase($row);
    }

    /**
     * Convert multiple database rows to BiTransaction entities
     *
     * @param array $rows Database rows
     * @return BiTransaction[]
     */
    private function entitiesToArray(array $rows): array
    {
        return array_map(
            fn(array $row) => $this->entityFromRow($row),
            $rows
        );
    }

    /**
     * Build parameter array for prepared statements
     *
     * Filters null values for optional parameters
     *
     * @param array $params Parameter map
     * @return array Filtered parameters
     */
    private function buildParams(array $params): array
    {
        return array_filter($params, fn($value) => $value !== null);
    }
}
