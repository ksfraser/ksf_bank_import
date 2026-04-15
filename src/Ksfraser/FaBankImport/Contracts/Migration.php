<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Database;

/**
 * Migration Interface
 * 
 * Defines contract for database migrations.
 * Each migration has an up() method to apply changes and down() method to revert.
 */
interface Migration
{
    /**
     * Get migration version number (e.g., "001")
     */
    public function version(): string;

    /**
     * Get migration description
     */
    public function description(): string;

    /**
     * Apply migration (up)
     */
    public function up(\PDO $pdo): void;

    /**
     * Revert migration (down)
     */
    public function down(\PDO $pdo): void;
}
