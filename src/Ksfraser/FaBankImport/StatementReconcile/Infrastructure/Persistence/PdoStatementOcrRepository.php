<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence;

use PDO;
use PDOException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\StatementOcrRepositoryInterface;

/**
 * PDO-backed implementation of StatementOcrRepositoryInterface.
 *
 * Table: bi_statement_ocr
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence
 * @author  Kevin Fraser
 */
final class PdoStatementOcrRepository implements StatementOcrRepositoryInterface
{
    private const TABLE = 'bi_statement_ocr';

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
    public function save(StatementOcr $entity): int
    {
        $data = $entity->toStorageArray();

        if ($entity->getId() === null) {
            return $this->insert($data);
        }

        $this->update($entity->getId(), $data);
        return $entity->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?StatementOcr
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
    public function findByAccountIdentifier(string $accountIdentifier): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `' . self::TABLE . '`
             WHERE `account_identifier` = :acct
             ORDER BY `statement_end_date` DESC'
        );
        $stmt->execute([':acct' => $accountIdentifier]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows ?: []);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param array $data
     * @return int New row ID.
     */
    private function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO `bi_statement_ocr`
                (`account_identifier`, `statement_start_date`, `statement_end_date`,
                 `opening_balance`, `closing_balance`, `due_date`,
                 `lines_json`, `raw_ocr_json`, `model_metadata`)
            VALUES
                (:account_identifier, :statement_start_date, :statement_end_date,
                 :opening_balance, :closing_balance, :due_date,
                 :lines_json, :raw_ocr_json, :model_metadata)
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':account_identifier'   => $data['account_identifier'],
                ':statement_start_date' => $data['statement_start_date'],
                ':statement_end_date'   => $data['statement_end_date'],
                ':opening_balance'      => $data['opening_balance'],
                ':closing_balance'      => $data['closing_balance'],
                ':due_date'             => $data['due_date'],
                ':lines_json'           => $data['lines_json'],
                ':raw_ocr_json'         => $data['raw_ocr_json'],
                ':model_metadata'       => $data['model_metadata'],
            ]);
        } catch (PDOException $e) {
            throw StatementOcrException::forReason('DB insert failed: ' . $e->getMessage());
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
            UPDATE `bi_statement_ocr` SET
                `account_identifier`   = :account_identifier,
                `statement_start_date` = :statement_start_date,
                `statement_end_date`   = :statement_end_date,
                `opening_balance`      = :opening_balance,
                `closing_balance`      = :closing_balance,
                `due_date`             = :due_date,
                `lines_json`           = :lines_json,
                `raw_ocr_json`         = :raw_ocr_json,
                `model_metadata`       = :model_metadata
            WHERE `id` = :id
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge([':id' => $id], [
                ':account_identifier'   => $data['account_identifier'],
                ':statement_start_date' => $data['statement_start_date'],
                ':statement_end_date'   => $data['statement_end_date'],
                ':opening_balance'      => $data['opening_balance'],
                ':closing_balance'      => $data['closing_balance'],
                ':due_date'             => $data['due_date'],
                ':lines_json'           => $data['lines_json'],
                ':raw_ocr_json'         => $data['raw_ocr_json'],
                ':model_metadata'       => $data['model_metadata'],
            ]));
        } catch (PDOException $e) {
            throw StatementOcrException::forReason('DB update failed: ' . $e->getMessage());
        }
    }

    /**
     * Reconstitute a StatementOcr from a raw DB row.
     *
     * @param array $row
     * @return StatementOcr
     */
    private function hydrate(array $row): StatementOcr
    {
        $metadataArray = [
            'account_identifier'   => $row['account_identifier'],
            'statement_start_date' => $row['statement_start_date'],
            'statement_end_date'   => $row['statement_end_date'],
            'opening_balance'      => (string) $row['opening_balance'],
            'closing_balance'      => (string) $row['closing_balance'],
            'due_date'             => $row['due_date'],
        ];

        $linesArray = (array) json_decode((string) ($row['lines_json'] ?? '[]'), true);

        $modelMeta    = (array) json_decode((string) ($row['model_metadata'] ?? '{}'), true);
        $rawOcrArray  = [
            'raw_json'      => (string) ($row['raw_ocr_json'] ?? '{}'),
            'model_name'    => (string) ($modelMeta['model_name']    ?? 'unknown'),
            'model_version' => isset($modelMeta['model_version']) ? (string) $modelMeta['model_version'] : null,
        ];

        return StatementOcr::fromDatabase($row, $metadataArray, $linesArray, $rawOcrArray);
    }
}
