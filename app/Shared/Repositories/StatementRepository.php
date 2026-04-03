<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Shared\Repositories;

use PDO;
use PDOStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidStatementException;
use Ksfraser\FaBankImport\Shared\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\RepositoryException;

/**
 * StatementRepository Implementation
 *
 * Handles persistence and retrieval of BiStatement entities from the database.
 * Implements the StatementRepositoryInterface contract with full CRUD operations,
 * query filtering, and bulk operations.
 *
 * @package Ksfraser\FaBankImport\Shared\Repositories
 */
final class StatementRepository implements StatementRepositoryInterface
{
    /**
     * @var PDO Database connection
     */
    private PDO $pdo;

    /**
     * @var string Table name for statements
     */
    private string $table = TB_PREF . 'bi_statements';

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Find a statement by ID
     *
     * @param int $id Statement ID
     * @return BiStatement
     * @throws EntityNotFoundException If statement not found
     * @throws RepositoryException If database error occurs
     */
    public function findById(int $id): BiStatement
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                throw new EntityNotFoundException(
                    "Statement with ID {$id} not found"
                );
            }

            return $this->entityFromRow($row);
        } catch (EntityNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding statement by ID: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find statements by bank ID
     *
     * @param string $bankId Bank identifier
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiStatement[]
     * @throws RepositoryException If database error occurs
     */
    public function findByBankId(string $bankId, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE bankid = :bankId";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['bankId' => $bankId, 'limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding statements by bank: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find statements by account ID
     *
     * @param string $acctId Account identifier
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiStatement[]
     * @throws RepositoryException If database error occurs
     */
    public function findByAcctId(string $acctId, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE acctid = :acctId";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->buildParams(['acctId' => $acctId, 'limit' => $limit, 'offset' => $offset]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding statements by account: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find statements by date range
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiStatement[]
     * @throws RepositoryException If database error occurs
     */
    public function findByDateRange(string $startDate, string $endDate, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE statement_date BETWEEN :startDate AND :endDate";
            
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
                'offset' => $offset,
            ]));

            return $this->entitiesToArray($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error finding statements by date range: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Save a statement entity
     *
     * @param BiStatement $statement Statement to save
     * @return int ID of saved statement
     * @throws InvalidStatementException If statement invalid
     * @throws RepositoryException If database error occurs
     */
    public function save(BiStatement $statement): int
    {
        try {
            $data = $statement->toDatabase();

            // Check if insert or update
            if (!empty($data['id'])) {
                return $this->update($statement);
            }

            // Remove ID for insert
            unset($data['id']);

            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ":{$col}", $columns);

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);

            return (int)$this->pdo->lastInsertId();
        } catch (InvalidStatementException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error saving statement: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Update a statement entity
     *
     * @param BiStatement $statement Statement to update
     * @return int ID of updated statement
     * @throws InvalidStatementException If statement invalid
     * @throws EntityNotFoundException If statement not found
     * @throws RepositoryException If database error occurs
     */
    public function update(BiStatement $statement): int
    {
        try {
            $data = $statement->toDatabase();

            if (empty($data['id'])) {
                throw new EntityNotFoundException('Cannot update statement without ID');
            }

            $id = $data['id'];
            unset($data['id']);

            $updates = array_map(fn($col) => "{$col} = :{$col}", array_keys($data));

            $sql = sprintf(
                "UPDATE %s SET %s WHERE id = :id",
                $this->table,
                implode(', ', $updates)
            );

            $data['id'] = $id;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);

            return $id;
        } catch (EntityNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error updating statement: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Delete a statement
     *
     * @param int $id Statement ID
     * @return bool True on success
     * @throws EntityNotFoundException If statement not found
     * @throws RepositoryException If database error occurs
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new EntityNotFoundException("Statement with ID {$id} not found");
            }

            return true;
        } catch (EntityNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error deleting statement: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get total count of statements
     *
     * @return int
     * @throws RepositoryException If database error occurs
     */
    public function count(): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$this->table}");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['count'] ?? 0);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error counting statements: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Bulk insert multiple statements
     *
     * @param BiStatement[] $statements Statements to insert
     * @return int[] IDs of inserted statements
     * @throws RepositoryException If database error occurs
     */
    public function bulkInsert(array $statements): array
    {
        if (empty($statements)) {
            return [];
        }

        try {
            $this->pdo->beginTransaction();

            $ids = [];
            foreach ($statements as $statement) {
                $id = $this->save($statement);
                $ids[] = $id;
            }

            $this->pdo->commit();
            return $ids;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RepositoryException(
                "Error in bulk insert: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Bulk update multiple statements
     *
     * @param BiStatement[] $statements Statements to update
     * @return int Number of updated statements
     * @throws RepositoryException If database error occurs
     */
    public function bulkUpdate(array $statements): int
    {
        try {
            $this->pdo->beginTransaction();

            $count = 0;
            foreach ($statements as $statement) {
                $this->update($statement);
                $count++;
            }

            $this->pdo->commit();
            return $count;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RepositoryException(
                "Error in bulk update: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Bulk delete statements by IDs
     *
     * @param int[] $ids Statement IDs to delete
     * @return int Number of deleted statements
     * @throws RepositoryException If database error occurs
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM {$this->table} WHERE id IN ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($ids);

            return $stmt->rowCount();
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error in bulk delete: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find statements by status
     *
     * @param string $status Status value
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiStatement[]
     * @throws RepositoryException If database error occurs
     */
    public function findByStatus(string $status, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE status = :status";
            
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
                "Error finding statements by status: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find unprocessed statements
     *
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiStatement[]
     * @throws RepositoryException If database error occurs
     */
    public function findUnprocessed(?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE status = 0";
            
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
                "Error finding unprocessed statements: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Find processed statements
     *
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return BiStatement[]
     * @throws RepositoryException If database error occurs
     */
    public function findProcessed(?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE status != 0";
            
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
                "Error finding processed statements: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Count statements by status
     *
     * @param string $status Status value
     * @return int
     * @throws RepositoryException If database error occurs
     */
    public function countByStatus(string $status): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE status = :status"
            );
            $stmt->execute(['status' => $status]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['count'] ?? 0);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Error counting by status: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Convert database row to BiStatement entity
     *
     * @param array $row Database row
     * @return BiStatement
     */
    private function entityFromRow(array $row): BiStatement
    {
        return BiStatement::fromDatabase($row);
    }

    /**
     * Convert multiple database rows to BiStatement entities
     *
     * @param array $rows Database rows
     * @return BiStatement[]
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
