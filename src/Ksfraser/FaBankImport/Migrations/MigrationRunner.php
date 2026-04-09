<?php

namespace Ksfraser\FaBankImport\Migrations;

use PDO;

/**
 * Migration Runner
 *
 * Manages execution of database migrations using the application's PDO connection.
 * Tracks which migrations have been applied and supports rollback.
 *
 * @package Ksfraser\FaBankImport\Migrations
 * @since 2026-04-09
 */
class MigrationRunner
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @var array Registered migrations
     */
    private array $migrations = [];

    /**
     * @var string Namespace for migration classes
     */
    private const MIGRATION_NAMESPACE = 'Ksfraser\FaBankImport\Migrations\\';

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Register a migration
     *
     * @param MigrationInterface $migration
     * @return self
     */
    public function register(MigrationInterface $migration): self
    {
        $this->migrations[$migration->getVersion()] = $migration;
        return $this;
    }

    /**
     * Register all available migrations
     *
     * @return self
     */
    public function registerAll(): self
    {
        // Register all migration classes
        $this->register(new CreateBiTransactionsDupeTable());
        
        // Add future migrations here as they're created
        // $this->register(new CreateNextFeatureTable());
        
        return $this;
    }

    /**
     * Run all pending migrations
     *
     * @return array List of executed migrations
     */
    public function runPending(): array
    {
        $executed = [];
        $applied = $this->getAppliedMigrations();

        foreach ($this->migrations as $version => $migration) {
            if (!in_array($version, $applied)) {
                try {
                    $migration->up($this->pdo);
                    $executed[] = $version;
                    echo "✓ Executed: {$version}\n";
                } catch (\Exception $e) {
                    echo "✗ Error executing {$version}: " . $e->getMessage() . "\n";
                    throw $e;
                }
            }
        }

        return $executed;
    }

    /**
     * Run a specific migration
     *
     * @param string $version
     * @return bool
     */
    public function run(string $version): bool
    {
        if (!isset($this->migrations[$version])) {
            throw new \InvalidArgumentException("Migration not found: $version");
        }

        $migration = $this->migrations[$version];
        $applied = $this->getAppliedMigrations();

        if (in_array($version, $applied)) {
            echo "Already applied: $version\n";
            return true;
        }

        try {
            $migration->up($this->pdo);
            echo "✓ Executed: {$version}\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ Failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Rollback the last migration batch
     *
     * @return array List of rolled back migrations
     */
    public function rollbackBatch(): array
    {
        $batch = $this->getLatestBatch();
        if ($batch === 0) {
            echo "No migrations to rollback\n";
            return [];
        }

        $applied = $this->getAppliedMigrationsByBatch($batch);
        $rolledBack = [];

        foreach (array_reverse($applied) as $version) {
            if (!isset($this->migrations[$version])) {
                continue;
            }

            try {
                $this->migrations[$version]->down($this->pdo);
                $rolledBack[] = $version;
                echo "✓ Rolled back: {$version}\n";
            } catch (\Exception $e) {
                echo "✗ Failed to rollback {$version}: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        return $rolledBack;
    }

    /**
     * Rollback a specific migration
     *
     * @param string $version
     * @return bool
     */
    public function rollback(string $version): bool
    {
        if (!isset($this->migrations[$version])) {
            throw new \InvalidArgumentException("Migration not found: $version");
        }

        try {
            $this->migrations[$version]->down($this->pdo);
            $this->removeMigrationRecord($version);
            echo "✓ Rolled back: {$version}\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ Failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Get list of applied migrations
     *
     * @return array
     */
    public function getAppliedMigrations(): array
    {
        try {
            $this->ensureMigrationsTable();
            $stmt = $this->pdo->query("SELECT version FROM db_migrations ORDER BY executed_at");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get pending migrations
     *
     * @return array
     */
    public function getPendingMigrations(): array
    {
        $applied = $this->getAppliedMigrations();
        $pending = [];

        foreach (array_keys($this->migrations) as $version) {
            if (!in_array($version, $applied)) {
                $pending[] = $version;
            }
        }

        return $pending;
    }

    /**
     * Get migration status
     *
     * @return array
     */
    public function getStatus(): array
    {
        $applied = $this->getAppliedMigrations();
        $status = [];

        foreach ($this->migrations as $version => $migration) {
            $status[] = [
                'version' => $version,
                'description' => $migration->getDescription(),
                'status' => in_array($version, $applied) ? 'Applied' : 'Pending',
            ];
        }

        return $status;
    }

    /**
     * Print migration status report
     *
     * @return void
     */
    public function printStatus(): void
    {
        echo "\n=== Migration Status ===\n";
        $status = $this->getStatus();

        foreach ($status as $migration) {
            $indicator = $migration['status'] === 'Applied' ? '✓' : '○';
            echo sprintf(
                "%s %-50s %s\n",
                $indicator,
                $migration['version'],
                $migration['description']
            );
        }

        $pending = count(array_filter($status, fn($s) => $s['status'] === 'Pending'));
        echo "\nPending migrations: {$pending}\n\n";
    }

    /**
     * Get latest batch number
     *
     * @return int
     */
    private function getLatestBatch(): int
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM db_migrations");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['batch'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get migrations for a specific batch
     *
     * @param int $batch
     * @return array
     */
    private function getAppliedMigrationsByBatch(int $batch): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT version FROM db_migrations WHERE batch = ? ORDER BY executed_at DESC");
            $stmt->execute([$batch]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Ensure migrations table exists
     *
     * @return void
     */
    private function ensureMigrationsTable(): void
    {
        try {
            $this->pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS db_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    batch INT DEFAULT 1,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
            );
        } catch (\Exception $e) {
            // Table already exists
        }
    }

    /**
     * Remove migration record
     *
     * @param string $version
     * @return void
     */
    private function removeMigrationRecord(string $version): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM db_migrations WHERE version = ?");
            $stmt->execute([$version]);
        } catch (\Exception $e) {
            // Table may not exist
        }
    }
}
