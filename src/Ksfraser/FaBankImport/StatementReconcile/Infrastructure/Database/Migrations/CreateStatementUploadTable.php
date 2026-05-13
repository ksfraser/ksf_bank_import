<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations;

use PDO;
use Ksfraser\FaBankImport\Infrastructure\Database\Migration;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationException;

/**
 * Creates the bi_statement_upload table.
 *
 * Stores one row per uploaded PDF statement file:
 * - Original filename and server-side storage path.
 * - File size for audit / housekeeping.
 * - bank_account_id (nullable; populated after user confirms the account on
 *   the account-confirmation screen, before the matching engine runs).
 * - statement_ocr_id (nullable FK → bi_statement_ocr; populated after OCR).
 * - Uploader identity and timestamp.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations
 * @author  Kevin Fraser
 */
final class CreateStatementUploadTable implements Migration
{
    public function name(): string
    {
        return '20260420_100002_create_statement_upload_table';
    }

    public function description(): string
    {
        return 'Create bi_statement_upload table for PDF statement file tracking';
    }

    public function up(PDO $pdo): void
    {
        try {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS `bi_statement_upload` (
                    `id`                   INT AUTO_INCREMENT PRIMARY KEY,
                    `original_filename`    VARCHAR(255)     NOT NULL    COMMENT 'Sanitised original filename from upload',
                    `stored_path`          VARCHAR(500)     NOT NULL    COMMENT 'Server-side path or object-storage key',
                    `file_size_bytes`      INT UNSIGNED     NOT NULL    COMMENT 'File size in bytes',
                    `bank_account_id`      INT              NULL        COMMENT 'FA 0_bank_accounts.id; set after account confirmation',
                    `statement_ocr_id`     INT              NULL        COMMENT 'FK to bi_statement_ocr.id; set after OCR',
                    `uploaded_by_user_id`  INT              NOT NULL DEFAULT 0 COMMENT 'FA user ID of uploader',
                    `uploaded_at`          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT `fk_stmt_upload_ocr`
                        FOREIGN KEY (`statement_ocr_id`)
                        REFERENCES `bi_statement_ocr` (`id`)
                        ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Uploaded PDF statement files'
            SQL);

            $pdo->exec(
                'CREATE INDEX `ix_stmt_upload_bank_acct` ON `bi_statement_upload` (`bank_account_id`)'
            );
            $pdo->exec(
                'CREATE INDEX `ix_stmt_upload_ocr` ON `bi_statement_upload` (`statement_ocr_id`)'
            );
            $pdo->exec(
                'CREATE INDEX `ix_stmt_upload_user` ON `bi_statement_upload` (`uploaded_by_user_id`)'
            );
        } catch (\PDOException $e) {
            throw new MigrationException(
                'Failed to create bi_statement_upload table: ' . $e->getMessage()
            );
        }
    }

    public function down(PDO $pdo): void
    {
        try {
            $pdo->exec('DROP TABLE IF EXISTS `bi_statement_upload`');
        } catch (\PDOException $e) {
            throw new MigrationException(
                'Failed to drop bi_statement_upload table: ' . $e->getMessage()
            );
        }
    }
}
