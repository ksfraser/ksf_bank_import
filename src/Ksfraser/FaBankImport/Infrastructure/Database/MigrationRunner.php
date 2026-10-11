<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Database;

use PDO;

/**
 * MigrationRunner - Executes migrations and tracks execution history
 * 
 * Manages database migrations by:
 * - Tracking which migrations have been executed (schema_migrations table)
 * - Executing pending migrations in order
 * - Supporting rollback of individual migrations
 * - Reporting migration status
 * 
 * @author Kevin Fraser
 * @since 2.2.0
 */
final class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsTable;

    /**
     * @param PDO $pdo Database connection
     * @param string $migrationsTable Name of table tracking executed migrations
     */
    public function __construct(PDO $pdo, string $migrationsTable = 'schema_migrations')
    {
        $this->pdo = $pdo;
        $this->migrationsTable = $migrationsTable;
        $this->initializeMigrationsTable();
    }

    /**
     * Initialize migrations tracking table
     */
    private function initializeMigrationsTable(): void
    {
        $tableName = $this->migrationsTable;

        try {
            // MySQL/MariaDB compatible schema creation
            $sql = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `migration` VARCHAR(255) NOT NULL UNIQUE,
                    `batch` INT NOT NULL,
                    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to initialize migrations table: " . $e->getMessage());
        }
    }

    /**
     * Run all pending migrations
     * 
     * @param array<Migration> $migrations List of migrations to execute
     * @return array<string> Names of executed migrations
     * @throws MigrationException on failure
     */
    public function runPending(array $migrations): array
    {
        $executed = [];
        $batch = $this->getNextBatch();

        foreach ($migrations as $migration) {
            if ($this->hasRun($migration->name())) {
                continue;
            }

            try {
                $migration->up($this->pdo);
                $this->recordMigration($migration->name(), $batch);
                $executed[] = $migration->name();
            } catch (\Throwable $e) {
                throw new MigrationException(
                    "Migration failed: {$migration->name()} - " . $e->getMessage()
                );
            }
        }

        return $executed;
    }

    /**
     * Rollback the last batch of migrations
     * 
     * @param array<Migration> $migrations All available migrations (keyed by name)
     * @return array<string> Names of rolled-back migrations
     * @throws MigrationException on failure
     */
    public function rollbackLast(array $migrations): array
    {
        $lastBatch = $this->getLastBatch();
        if ($lastBatch === null) {
            return [];
        }

        $rolledBack = [];
        $executed = $this->getExecutedMigrations($lastBatch);

        // Rollback in reverse order
        foreach (array_reverse($executed) as $migrationName) {
            if (!isset($migrations[$migrationName])) {
                throw new MigrationException("Migration not found: $migrationName");
            }

            try {
                $migrations[$migrationName]->down($this->pdo);
                $this->forgetMigration($migrationName);
                $rolledBack[] = $migrationName;
            } catch (\Throwable $e) {
                throw new MigrationException(
                    "Rollback failed: $migrationName - " . $e->getMessage()
                );
            }
        }

        return $rolledBack;
    }

    /**
     * Get list of executed migrations
     * 
     * @return array<string>
     */
    public function getExecuted(): array
    {
        try {
            $result = $this->pdo->query(
                "SELECT migration FROM {$this->migrationsTable} ORDER BY batch ASC, id ASC"
            );
            return $result->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to retrieve executed migrations: " . $e->getMessage());
        }
    }

    /**
     * Get status of all migrations
     * 
     * @param array<Migration> $migrations
     * @return array<string, bool> Keyed by migration name, value is executed (true) or pending (false)
     */
    public function getStatus(array $migrations): array
    {
        $executed = array_flip($this->getExecuted());
        $status = [];

        foreach ($migrations as $migration) {
            $status[$migration->name()] = isset($executed[$migration->name()]);
        }

        return $status;
    }

    /**
     * Check if a migration has been executed
     */
    private function hasRun(string $migrationName): bool
    {
        try {
            $result = $this->pdo->prepare(
                "SELECT 1 FROM {$this->migrationsTable} WHERE migration = ? LIMIT 1"
            );
            $result->execute([$migrationName]);
            return $result->fetchColumn() !== false;
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to check migration status: " . $e->getMessage());
        }
    }

    /**
     * Record that a migration was executed
     */
    private function recordMigration(string $migrationName, int $batch): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)"
            );
            $stmt->execute([$migrationName, $batch]);
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to record migration: " . $e->getMessage());
        }
    }

    /**
     * Remove migration record (on rollback)
     */
    private function forgetMigration(string $migrationName): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->migrationsTable} WHERE migration = ?"
            );
            $stmt->execute([$migrationName]);
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to forget migration: " . $e->getMessage());
        }
    }

    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        $lastBatch = $this->getLastBatch();
        return $lastBatch !== null ? $lastBatch + 1 : 1;
    }

    /**
     * Get last executed batch number
     */
    private function getLastBatch(): ?int
    {
        try {
            $result = $this->pdo->query(
                "SELECT MAX(batch) FROM {$this->migrationsTable}"
            );
            $batch = $result->fetchColumn();
            return $batch !== null ? (int)$batch : null;
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to get last batch: " . $e->getMessage());
        }
    }

    /**
     * Get migrations executed in a specific batch
     * 
     * @return array<string>
     */
    private function getExecutedMigrations(int $batch): array
    {
        try {
            $result = $this->pdo->prepare(
                "SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id DESC"
            );
            $result->execute([$batch]);
            return $result->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            throw new MigrationException("Failed to retrieve batch migrations: " . $e->getMessage());
        }
    }
}
