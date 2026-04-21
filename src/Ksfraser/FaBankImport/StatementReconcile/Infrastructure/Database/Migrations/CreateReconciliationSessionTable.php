<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations;

use PDO;
use Ksfraser\FaBankImport\Infrastructure\Database\Migration;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationException;

/**
 * Creates the bi_reconciliation_session table.
 *
 * Each row represents one reconciliation pass for a parsed statement:
 * - Matched pairs (statement line ↔ FA bank transaction) stored as JSON.
 * - Unmatched line IDs on both sides stored as JSON arrays.
 * - Status: pending → approved.
 * - Audit columns: who approved and when.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations
 * @author  Kevin Fraser
 */
final class CreateReconciliationSessionTable implements Migration
{
    public function name(): string
    {
        return '20260420_100001_create_reconciliation_session_table';
    }

    public function description(): string
    {
        return 'Create bi_reconciliation_session table for statement reconciliation workflow';
    }

    public function up(PDO $pdo): void
    {
        try {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS `bi_reconciliation_session` (
                    `id`                              INT AUTO_INCREMENT PRIMARY KEY,
                    `statement_ocr_id`                INT          NOT NULL COMMENT 'FK to bi_statement_ocr.id',
                    `bank_account_id`                 INT          NULL     COMMENT 'FA 0_bank_accounts.id confirmed for this session',
                    `matched_pairs_json`              LONGTEXT     NULL     COMMENT 'JSON: array of MatchedPair objects',
                    `unmatched_statement_line_ids`    TEXT         NULL     COMMENT 'JSON: string array of statement line IDs',
                    `unmatched_bank_transaction_ids`  TEXT         NULL     COMMENT 'JSON: int array of FA bank tx IDs',
                    `status`                          VARCHAR(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending|approved',
                    `created_by_user_id`              INT          NULL     COMMENT 'FA user who initiated the reconciliation',
                    `approved_by_user_id`             INT          NULL     COMMENT 'FA user who approved (commit to FA)',
                    `approved_at`                     TIMESTAMP    NULL     COMMENT 'When approved',
                    `created_at`                      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`                      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT `fk_recon_stmt_ocr`
                        FOREIGN KEY (`statement_ocr_id`)
                        REFERENCES `bi_statement_ocr` (`id`)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Reconciliation session per statement'
            SQL);

            $pdo->exec(
                'CREATE INDEX `ix_recon_session_stmt_ocr` ON `bi_reconciliation_session` (`statement_ocr_id`)'
            );
            $pdo->exec(
                'CREATE INDEX `ix_recon_session_status` ON `bi_reconciliation_session` (`status`)'
            );
        } catch (\PDOException $e) {
            throw new MigrationException(
                'Failed to create bi_reconciliation_session table: ' . $e->getMessage()
            );
        }
    }

    public function down(PDO $pdo): void
    {
        try {
            // Drop child before parent.
            $pdo->exec('DROP TABLE IF EXISTS `bi_reconciliation_session`');
        } catch (\PDOException $e) {
            throw new MigrationException(
                'Failed to drop bi_reconciliation_session table: ' . $e->getMessage()
            );
        }
    }
}
