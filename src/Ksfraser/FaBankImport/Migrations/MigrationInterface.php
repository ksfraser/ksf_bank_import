<?php

namespace Ksfraser\FaBankImport\Migrations;

use PDO;

/**
 * Migration Interface
 *
 * All migrations must implement this interface.
 *
 * @package Ksfraser\FaBankImport\Migrations
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
 */
interface MigrationInterface
{
    /**
     * Get migration version/ID
     *
     * @return string Unique identifier (e.g., '001_create_bi_transactions_dupe_table')
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     */
    public function getVersion(): string;

    /**
     * Get migration description
     *
     * @return string Human-readable description
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     */
    public function getDescription(): string;

    /**
     * Execute the migration (up)
     *
     * @param PDO $pdo
     * @return bool True if successful
     * @throws \Exception On failure
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     */
    public function up(PDO $pdo): bool;

    /**
     * Rollback the migration (down)
     *
     * @param PDO $pdo
     * @return bool True if successful
     * @throws \Exception On failure
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     */
    public function down(PDO $pdo): bool;
}
