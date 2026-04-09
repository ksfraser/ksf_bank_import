<?php

namespace Ksfraser\FaBankImport\Migrations;

use PDO;

/**
 * Migration Interface
 *
 * All migrations must implement this interface.
 *
 * @package Ksfraser\FaBankImport\Migrations
 */
interface MigrationInterface
{
    /**
     * Get migration version/ID
     *
     * @return string Unique identifier (e.g., '001_create_bi_transactions_dupe_table')
     */
    public function getVersion(): string;

    /**
     * Get migration description
     *
     * @return string Human-readable description
     */
    public function getDescription(): string;

    /**
     * Execute the migration (up)
     *
     * @param PDO $pdo
     * @return bool True if successful
     * @throws \Exception On failure
     */
    public function up(PDO $pdo): bool;

    /**
     * Rollback the migration (down)
     *
     * @param PDO $pdo
     * @return bool True if successful
     * @throws \Exception On failure
     */
    public function down(PDO $pdo): bool;
}
