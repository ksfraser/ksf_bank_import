<?php

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use DateTime;
use PDO;
use PDOStatement;

/**
 * DuplicateTransactionRepository
 *
 * Data access layer for DuplicateTransaction entities.
 * Handles all operations on the bi_transactions_dupe staging table.
 *
 * @package Ksfraser\FaBankImport\Repositories
 * @since 2026-04-08
 */
final class DuplicateTransactionRepository implements IDuplicateTransactionRepository
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @var string Table name
     */
    private const TABLE = 'bi_transactions_dupe';
    private const AUDIT_TABLE = 'bi_transactions_dupe_audit';

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Save a duplicate transaction
     *
     * @param DuplicateTransaction $entity
     * @return int Inserted ID or existing ID
     */
    public function save(DuplicateTransaction $entity): int
    {
        $data = $entity->toArray();

        // Remove ID if not set (new record)
        $duplicateId = $data['duplicate_id'];
        if (is_null($duplicateId)) {
            unset($data['duplicate_id']);
        }

        if ($duplicateId) {
            // Update existing
            $this->update($data, $duplicateId);
            return $duplicateId;
        } else {
            // Insert new
            return $this->insert($data);
        }
    }

    /**
     * Insert new record
     *
     * @param array $data
     * @return int Last insert ID
     */
    private function insert(array $data): int
    {
        unset($data['created_at'], $data['updated_at']); // DB will set these

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . self::TABLE . " ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update existing record
     *
     * @param array $data
     * @param int $duplicateId
     * @return bool
     */
    private function update(array $data, int $duplicateId): bool
    {
        unset($data['duplicate_id'], $data['created_at']); // Don't update these

        // Store decision history in audit table before updating
        if (isset($data['decision_status'])) {
            $this->auditDecision($duplicateId, $data);
        }

        $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
        $sql = "UPDATE " . self::TABLE . " SET $setClause WHERE duplicate_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $values = array_merge(array_values($data), [$duplicateId]);

        return $stmt->execute($values);
    }

    /**
     * Audit decision changes
     *
     * @param int $duplicateId
     * @param array $changeData
     * @return void
     */
    private function auditDecision(int $duplicateId, array $changeData): void
    {
        $sql = "INSERT INTO " . self::AUDIT_TABLE . "
                (duplicate_id, decision_status, decided_by, reason, changed_at)
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $duplicateId,
            $changeData['decision_status'] ?? null,
            $changeData['decided_by'] ?? null,
            $changeData['reason'] ?? null,
        ]);
    }

    /**
     * Find by ID
     *
     * @param int $duplicateId
     * @return DuplicateTransaction|null
     */
    public function findById(int $duplicateId): ?DuplicateTransaction
    {
        $sql = "SELECT * FROM " . self::TABLE . " WHERE duplicate_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$duplicateId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? DuplicateTransaction::fromDatabaseRow($row) : null;
    }

    /**
     * Find by transaction code
     *
     * @param string $transactionCode
     * @return DuplicateTransaction|null
     */
    public function findByTransactionCode(string $transactionCode): ?DuplicateTransaction
    {
        $sql = "SELECT * FROM " . self::TABLE . " WHERE transaction_code = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transactionCode]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? DuplicateTransaction::fromDatabaseRow($row) : null;
    }

    /**
     * Find all pending duplicates
     *
     * @param int|null $bankAccountId Filter by bank account (optional)
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findPending(
        ?int $bankAccountId = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM " . self::TABLE . "
                WHERE decision_status = 'PENDING'";

        $params = [];

        if ($bankAccountId) {
            $sql .= " AND bank_account_id = ?";
            $params[] = $bankAccountId;
        }

        $sql .= " ORDER BY confidence_score DESC, trans_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->hydrateMultiple($stmt);
    }

    /**
     * Find duplicates by status
     *
     * @param string $status
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByStatus(
        string $status,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM " . self::TABLE . "
                WHERE decision_status = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status, $limit, $offset]);

        return $this->hydrateMultiple($stmt);
    }

    /**
     * Find high-confidence matches
     *
     * @param float $minConfidence Minimum confidence score (0-100)
     * @param int $bankAccountId
     * @param int $limit
     * @return array
     */
    public function findHighConfidenceMatches(
        float $minConfidence = 85.0,
        ?int $bankAccountId = null,
        int $limit = 100
    ): array {
        $sql = "SELECT * FROM " . self::TABLE . "
                WHERE decision_status = 'PENDING'
                AND confidence_score >= ?";

        $params = [$minConfidence];

        if ($bankAccountId) {
            $sql .= " AND bank_account_id = ?";
            $params[] = $bankAccountId;
        }

        $sql .= " ORDER BY confidence_score DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->hydrateMultiple($stmt);
    }

    /**
     * Get pending count
     *
     * @param int|null $bankAccountId
     * @return int
     */
    public function countPending(?int $bankAccountId = null): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM " . self::TABLE . "
                WHERE decision_status = 'PENDING'";

        $params = [];

        if ($bankAccountId) {
            $sql .= " AND bank_account_id = ?";
            $params[] = $bankAccountId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($result['cnt'] ?? 0);
    }

    /**
     * Get decision statistics
     *
     * @param DateTime|null $from Filter from date
     * @param DateTime|null $to Filter to date
     * @return array
     */
    public function getDecisionStats(?DateTime $from = null, ?DateTime $to = null): array
    {
        $sql = "SELECT
                    decision_status,
                    COUNT(*) as count,
                    ROUND(AVG(confidence_score), 2) as avg_confidence,
                    MIN(created_at) as first_review,
                    MAX(decided_at) as last_decision
                FROM " . self::TABLE;

        $params = [];
        $conditions = [];

        if ($from) {
            $conditions[] = "created_at >= ?";
            $params[] = $from->format('Y-m-d H:i:s');
        }

        if ($to) {
            $conditions[] = "created_at <= ?";
            $params[] = $to->format('Y-m-d H:i:s');
        }

        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY decision_status";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Delete a duplicate (hard delete - use for cleanup only)
     *
     * @param int $duplicateId
     * @return bool
     */
    public function delete(int $duplicateId): bool
    {
        $sql = "DELETE FROM " . self::TABLE . " WHERE duplicate_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$duplicateId]);
    }

    /**
     * Hydrate multiple records into entities
     *
     * @param PDOStatement $stmt
     * @return array
     */
    private function hydrateMultiple(PDOStatement $stmt): array
    {
        $entities = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entities[] = DuplicateTransaction::fromDatabaseRow($row);
        }

        return $entities;
    }

    /**
     * Get pending duplicates view (dashboard)
     *
     * @param int|null $bankAccountId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPendingDuplicatesForDashboard(
        ?int $bankAccountId = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM v_pending_duplicates";

        $params = [];

        if ($bankAccountId) {
            $sql .= " WHERE bank_account_id = ?";
            $params[] = $bankAccountId;
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->hydrateMultiple($stmt);
    }

    /**
     * Get audit trail for a duplicate
     *
     * @param int $duplicateId
     * @return array
     */
    public function getAuditTrail(int $duplicateId): array
    {
        $sql = "SELECT * FROM " . self::AUDIT_TABLE . "
                WHERE duplicate_id = ?
                ORDER BY changed_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$duplicateId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Find matches for a specific transaction
     *
     * @param string $transactionCode
     * @return array
     */
    public function findMatchesForTransaction(string $transactionCode): array
    {
        $sql = "SELECT * FROM " . self::TABLE . "
                WHERE (transaction_code = ? OR matched_to_code = ?)
                ORDER BY confidence_score DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transactionCode, $transactionCode]);

        return $this->hydrateMultiple($stmt);
    }

    /**
     * Bulk update decision status
     *
     * @param array $duplicateIds
     * @param string $decisionStatus
     * @param string $decidedBy
     * @param string|null $reason
     * @return int Number of affected rows
     */
    public function bulkUpdateDecision(
        array $duplicateIds,
        string $decisionStatus,
        string $decidedBy,
        ?string $reason = null
    ): int {
        if (empty($duplicateIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($duplicateIds), '?'));

        $sql = "UPDATE " . self::TABLE . "
                SET decision_status = ?,
                    decided_by = ?,
                    reason = ?,
                    decided_at = NOW(),
                    updated_at = NOW()
                WHERE duplicate_id IN ($placeholders)";

        $params = [$decisionStatus, $decidedBy, $reason, ...$duplicateIds];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
