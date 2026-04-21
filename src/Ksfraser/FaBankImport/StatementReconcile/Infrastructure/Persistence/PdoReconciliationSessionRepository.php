<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence;

use PDO;
use PDOException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;

/**
 * PDO-backed implementation of ReconciliationSessionRepositoryInterface.
 *
 * Table: bi_reconciliation_session
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence
 * @author  Kevin Fraser
 */
final class PdoReconciliationSessionRepository implements ReconciliationSessionRepositoryInterface
{
    private const TABLE = 'bi_reconciliation_session';

    /** @var PDO */
    private $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ReconciliationSession $session): int
    {
        $data = $session->toStorageArray();

        if ($session->getId() === null) {
            return $this->insert($data);
        }

        $this->update($session->getId(), $data);
        return $session->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?ReconciliationSession
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `' . self::TABLE . '` WHERE `id` = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findLatestByStatementOcrId(int $statementOcrId): ?ReconciliationSession
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `' . self::TABLE . '`
             WHERE `statement_ocr_id` = :stmt_id
             ORDER BY `created_at` DESC
             LIMIT 1'
        );
        $stmt->execute([':stmt_id' => $statementOcrId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * {@inheritdoc}
     */
    public function approve(int $sessionId, int $userId): void
    {
        $session = $this->findById($sessionId);
        if ($session === null) {
            throw ReconciliationException::sessionNotFound($sessionId);
        }

        $session->approve($userId);
        $this->save($session);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param array $data Storage array from ReconciliationSession::toStorageArray().
     * @return int New row ID.
     */
    private function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO `bi_reconciliation_session`
                (`statement_ocr_id`, `matched_pairs_json`, `unmatched_statement_line_ids`,
                 `unmatched_bank_transaction_ids`, `status`, `persisted_by_user_id`, `persisted_at`)
            VALUES
                (:statement_ocr_id, :matched_pairs_json, :unmatched_statement_line_ids,
                 :unmatched_bank_transaction_ids, :status, :persisted_by_user_id, :persisted_at)
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':statement_ocr_id'              => $data['statement_ocr_id'],
                ':matched_pairs_json'             => $data['matched_pairs_json'],
                ':unmatched_statement_line_ids'  => $data['unmatched_statement_line_ids'],
                ':unmatched_bank_transaction_ids'=> $data['unmatched_bank_transaction_ids'],
                ':status'                        => $data['status'],
                ':persisted_by_user_id'          => $data['persisted_by_user_id'],
                ':persisted_at'                  => $data['persisted_at'],
            ]);
        } catch (PDOException $e) {
            throw ReconciliationException::forReason('DB insert failed: ' . $e->getMessage());
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param int   $id
     * @param array $data
     */
    private function update(int $id, array $data): void
    {
        $sql = <<<SQL
            UPDATE `bi_reconciliation_session` SET
                `matched_pairs_json`             = :matched_pairs_json,
                `unmatched_statement_line_ids`  = :unmatched_statement_line_ids,
                `unmatched_bank_transaction_ids`= :unmatched_bank_transaction_ids,
                `status`                        = :status,
                `persisted_by_user_id`          = :persisted_by_user_id,
                `persisted_at`                  = :persisted_at
            WHERE `id` = :id
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'                            => $id,
                ':matched_pairs_json'             => $data['matched_pairs_json'],
                ':unmatched_statement_line_ids'  => $data['unmatched_statement_line_ids'],
                ':unmatched_bank_transaction_ids'=> $data['unmatched_bank_transaction_ids'],
                ':status'                        => $data['status'],
                ':persisted_by_user_id'          => $data['persisted_by_user_id'],
                ':persisted_at'                  => $data['persisted_at'],
            ]);
        } catch (PDOException $e) {
            throw ReconciliationException::forReason('DB update failed: ' . $e->getMessage());
        }
    }

    /**
     * Reconstitute a ReconciliationSession from a raw DB row.
     *
     * @param array $row
     * @return ReconciliationSession
     */
    private function hydrate(array $row): ReconciliationSession
    {
        $pairs        = (array) json_decode((string) ($row['matched_pairs_json'] ?? '[]'), true);
        $unmatchedLines = (array) json_decode((string) ($row['unmatched_statement_line_ids'] ?? '[]'), true);
        $unmatchedBank  = (array) json_decode((string) ($row['unmatched_bank_transaction_ids'] ?? '[]'), true);

        return ReconciliationSession::fromDatabase($row, $pairs, $unmatchedLines, $unmatchedBank);
    }
}
