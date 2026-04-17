<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Database\Migrations;

use PDO;
use Ksfraser\FaBankImport\Infrastructure\Database\Migration;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationException;

/**
 * Initial Migration - Create Partner Tables
 * 
 * Creates the foundational tables for partner data management:
 * - bi_partners_data: Core partner information with learning metrics
 * 
 * @author Kevin Fraser
 * @since 2.2.0
 */
final class CreatePartnerTables implements Migration
{
    public function name(): string
    {
        return '20260416_140000_create_partner_tables';
    }

    public function description(): string
    {
        return 'Create bi_partners_data table for partner matching and training';
    }

    public function up(PDO $pdo): void
    {
        try {
            // Create table with MySQL/MariaDB-compatible syntax
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS `bi_partners_data` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `partner_type` VARCHAR(50) NOT NULL,
                    `occurrence_count` INT DEFAULT 0,
                    `last_matched_ts` TIMESTAMP NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            SQL);
            
            // Create indexes for common queries
            $pdo->exec("CREATE UNIQUE INDEX `uk_partner_name_type` ON `bi_partners_data` (`name`, `partner_type`)");
            $pdo->exec("CREATE INDEX `ix_partner_type` ON `bi_partners_data` (`partner_type`)");
            $pdo->exec("CREATE INDEX `ix_occurrence_count` ON `bi_partners_data` (`occurrence_count`)");
            $pdo->exec("CREATE INDEX `ix_last_matched_ts` ON `bi_partners_data` (`last_matched_ts`)");
        } catch (\PDOException $e) {
            throw new MigrationException(
                "Failed to create bi_partners_data table: " . $e->getMessage()
            );
        }
    }

    public function down(PDO $pdo): void
    {
        try {
            $pdo->exec('DROP TABLE IF EXISTS bi_partners_data');
        } catch (\PDOException $e) {
            throw new MigrationException(
                "Failed to drop bi_partners_data table: " . $e->getMessage()
            );
        }
    }
}
