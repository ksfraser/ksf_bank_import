<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Database;

use PDO;

/**
 * @BABOK CLASS ROLLUP: Migration — 4 methods; FR-006 (4)
 * @BABOK RTM: FR-006 / ARCH-007; file: Infrastructure/Database/Migration.php; coverage: docs/TestCases/ (file rollup)
 * Migration Interface
 * 
 * Defines the contract for database migrations.
 * Each migration represents a versioned database schema change.
 * 
 * @author Kevin Fraser
 * @since 2.2.0
 */
interface Migration
{
    /**
 * @BABOK FR-006 / ARCH-007 — method: name
     * Get the migration name/identifier
     * Should be unique and follow format: YYYYMMDD_HHMMSS_description
     * Example: 20260416_140000_create_partner_tables
     * 
     * @return string
     */
    public function name(): string;

    /**
 * @BABOK FR-006 / ARCH-007 — method: description
     * Get a description of what this migration does
     * 
     * @return string
     */
    public function description(): string;

    /**
 * @BABOK FR-006 / ARCH-007 — method: up
     * Execute the migration (apply changes)
     * 
     * @param PDO $pdo Database connection
     * @throws MigrationException if migration fails
     */
    public function up(PDO $pdo): void;

    /**
 * @BABOK FR-006 / ARCH-007 — method: down
     * Rollback the migration (undo changes)
     * 
     * @param PDO $pdo Database connection
     * @throws MigrationException if rollback fails
     */
    public function down(PDO $pdo): void;
}
