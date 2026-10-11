<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations;

use PDO;
use Ksfraser\FaBankImport\Infrastructure\Database\Migration;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationException;

/**
 * Creates the bi_statement_ocr table.
 *
 * Stores one row per uploaded CC statement PDF, including:
 * - Statement-level metadata (balances, dates, account id).
 * - All extracted line items as JSON.
 * - The raw OCR/LLM JSON response for audit / re-processing.
 * - Model metadata (which model produced the extraction).
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations
 * @author  Kevin Fraser
 */
final class CreateStatementOcrTable implements Migration
{
    public function name(): string
    {
        return '20260420_100000_create_statement_ocr_table';
    }

    public function description(): string
    {
        return 'Create bi_statement_ocr table for PDF CC statement OCR results and metadata';
    }

    public function up(PDO $pdo): void
    {
        try {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS `bi_statement_ocr` (
                    `id`                   INT AUTO_INCREMENT PRIMARY KEY,
                    `account_identifier`   VARCHAR(100)     NULL        COMMENT 'Card last-4 or account label',
                    `statement_start_date` DATE             NULL        COMMENT 'Start of statement period',
                    `statement_end_date`   DATE             NULL        COMMENT 'End of statement period',
                    `opening_balance`      DECIMAL(15, 2)   NULL        COMMENT 'Opening/previous balance',
                    `closing_balance`      DECIMAL(15, 2)   NULL        COMMENT 'Closing/new balance',
                    `due_date`             DATE             NULL        COMMENT 'Payment due date (nullable)',
                    `lines_json`           LONGTEXT         NOT NULL    COMMENT 'JSON array of extracted line items',
                    `raw_ocr_json`         LONGTEXT         NOT NULL    COMMENT 'Full raw JSON from OCR/LLM model',
                    `model_metadata`       TEXT             NULL        COMMENT 'JSON: model name/version used',
                    `created_at`           TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`           TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PDF CC statement OCR results'
            SQL);

            $pdo->exec(
                'CREATE INDEX `ix_stmt_ocr_account` ON `bi_statement_ocr` (`account_identifier`)'
            );
            $pdo->exec(
                'CREATE INDEX `ix_stmt_ocr_dates`   ON `bi_statement_ocr` (`statement_start_date`, `statement_end_date`)'
            );
        } catch (\PDOException $e) {
            throw new MigrationException(
                'Failed to create bi_statement_ocr table: ' . $e->getMessage()
            );
        }
    }

    public function down(PDO $pdo): void
    {
        try {
            $pdo->exec('DROP TABLE IF EXISTS `bi_statement_ocr`');
        } catch (\PDOException $e) {
            throw new MigrationException(
                'Failed to drop bi_statement_ocr table: ' . $e->getMessage()
            );
        }
    }
}
