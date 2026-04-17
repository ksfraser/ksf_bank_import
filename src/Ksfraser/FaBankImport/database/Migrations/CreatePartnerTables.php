<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Database\Migrations;

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
            // Create table with SQLite-compatible syntax (also works with MySQL)
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS bi_partners_data (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    partner_type TEXT NOT NULL,
                    occurrence_count INTEGER DEFAULT 0,
                    last_matched_ts DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            SQL);
            
            // Create indexes for common queries
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uk_partner_name_type ON bi_partners_data (name, partner_type)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS ix_partner_type ON bi_partners_data (partner_type)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS ix_occurrence_count ON bi_partners_data (occurrence_count)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS ix_last_matched_ts ON bi_partners_data (last_matched_ts)");
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
