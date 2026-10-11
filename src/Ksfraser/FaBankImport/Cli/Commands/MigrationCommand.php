<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Cli\Commands;

use Ksfraser\FaBankImport\Contracts\Command;
use Ksfraser\FaBankImport\Contracts\Logger;
use Ksfraser\FaBankImport\Infrastructure\Config\EnvironmentConfig;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationRunner;
use Ksfraser\FaBankImport\Infrastructure\Database\Migrations\CreatePartnerTables;
use PDO;

/**
 * MigrationCommand - Manage database migrations
 *
 * Provides database migration management with support for:
 * - up: Apply pending migrations
 * - down: Rollback the last batch
 * - status: Show migration status
 * - refresh: Rollback all and re-run migrations
 *
 * Usage:
 *   php app.php migrate [subcommand] [options]
 *   php app.php migrate up            - Apply pending migrations
 *   php app.php migrate down          - Rollback last batch
 *   php app.php migrate status        - Show migration status
 *   php app.php migrate refresh       - Rollback all and re-run
 *
 * @author Kevin Fraser
 * @since 2.2.0
 */
final class MigrationCommand implements Command
{
    private PDO $pdo;
    private MigrationRunner $runner;
    private array $migrations = [];

    public function __construct(private readonly Logger $logger)
    {
        $this->initializeDatabase();
        $this->registerMigrations();
    }

    /**
     * Initialize database connection from environment config
     */
    private function initializeDatabase(): void
    {
        try {
            $config = new EnvironmentConfig('dev');
            $creds = $config->getDatabaseCredentials();

            $this->pdo = new PDO(
                $creds['dsn'],
                $creds['user'],
                $creds['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $this->runner = new MigrationRunner($this->pdo);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to initialize database: " . $e->getMessage());
        }
    }

    /**
     * Register all available migrations
     */
    private function registerMigrations(): void
    {
        $this->migrations = [
            new CreatePartnerTables(),
        ];
    }

    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Manage database migrations';
    }

    public function execute(array $arguments = []): int
    {
        try {
            $subcommand = $arguments['_positional'][0] ?? 'status';

            return match ($subcommand) {
                'up' => $this->executeUp(),
                'down' => $this->executeDown(),
                'status' => $this->executeStatus(),
                'refresh' => $this->executeRefresh(),
                default => $this->executeStatus(),
            };
        } catch (\Throwable $e) {
            $this->outputError("Migration command failed: " . $e->getMessage());
            $this->logger->error("Migration command error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Execute pending migrations (up)
     */
    private function executeUp(): int
    {
        try {
            $this->outputInfo("Running pending migrations...\n");

            // MigrationRunner expects indexed array for runPending
            $executed = $this->runner->runPending($this->migrations);

            if (empty($executed)) {
                $this->outputInfo("No pending migrations to run.");
                return 0;
            }

            $this->outputSuccess("Applied " . count($executed) . " migration(s):\n");
            foreach ($executed as $migration) {
                $this->outputInfo("  ✓ " . $migration);
            }

            $this->logger->info("Migrations executed", ['count' => count($executed)]);
            return 0;
        } catch (\Exception $e) {
            $this->outputError("Failed to run migrations: " . $e->getMessage());
            $this->logger->error("Migration execution failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Rollback the last batch of migrations (down)
     */
    private function executeDown(): int
    {
        try {
            $this->outputInfo("Rolling back last migration batch...\n");

            // Convert indexed array to keyed array for rollbackLast
            $migrationsKeyed = [];
            foreach ($this->migrations as $migration) {
                $migrationsKeyed[$migration->name()] = $migration;
            }

            $rolledBack = $this->runner->rollbackLast($migrationsKeyed);

            if (empty($rolledBack)) {
                $this->outputInfo("No migrations to rollback.");
                return 0;
            }

            $this->outputSuccess("Rolled back " . count($rolledBack) . " migration(s):\n");
            foreach ($rolledBack as $migration) {
                $this->outputInfo("  ↺ " . $migration);
            }

            $this->logger->info("Migrations rolled back", ['count' => count($rolledBack)]);
            return 0;
        } catch (\Exception $e) {
            $this->outputError("Rollback failed: " . $e->getMessage());
            $this->logger->error("Migration rollback failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Show migration status
     */
    private function executeStatus(): int
    {
        try {
            $status = $this->runner->getStatus($this->migrations);

            $this->outputInfo("\nMigration Status:\n");
            $this->outputInfo(str_repeat("-", 50) . "\n");

            $executed = [];
            $pending = [];

            foreach ($status as $migrationName => $isExecuted) {
                if ($isExecuted) {
                    $executed[] = $migrationName;
                } else {
                    $pending[] = $migrationName;
                }
            }

            if (empty($executed)) {
                $this->outputInfo("No migrations have been executed yet.\n");
            } else {
                $this->outputInfo("Executed Migrations (" . count($executed) . "):\n");
                foreach ($executed as $location) {
                    $this->outputSuccess("  ✓ " . $location . "\n");
                }
            }

            $this->outputInfo("\nPending Migrations:\n");

            if (empty($pending)) {
                $this->outputSuccess("  ✓ All migrations have been applied.\n");
            } else {
                // Find migration objects to display descriptions
                $migrationsByName = [];
                foreach ($this->migrations as $migration) {
                    $migrationsByName[$migration->name()] = $migration;
                }

                foreach ($pending as $migrationName) {
                    $desc = isset($migrationsByName[$migrationName]) 
                        ? $migrationsByName[$migrationName]->description() 
                        : '(no description)';
                    $this->outputInfo("  ⧖ " . $migrationName . " - " . $desc . "\n");
                }
            }

            $this->outputInfo(str_repeat("-", 50) . "\n");
            return 0;
        } catch (\Exception $e) {
            $this->outputError("Failed to get migration status: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Refresh database (rollback all and re-run)
     */
    private function executeRefresh(): int
    {
        try {
            $this->outputWarning("Refreshing database (this will rollback all migrations and re-run them)...\n");

            // Convert indexed array to keyed array for rollbackLast
            $migrationsKeyed = [];
            foreach ($this->migrations as $migration) {
                $migrationsKeyed[$migration->name()] = $migration;
            }

            // Get current status
            $status = $this->runner->getStatus($this->migrations);

            if (in_array(true, $status, true)) {
                // Rollback all migrations
                $this->outputInfo("Rolling back all migrations...\n");
                while (true) {
                    $rolledBack = $this->runner->rollbackLast($migrationsKeyed);
                    if (empty($rolledBack)) {
                        break;
                    }
                }
                $this->outputSuccess("All migrations rolled back.\n\n");
            }

            // Re-run migrations
            $this->outputInfo("Applying all migrations...\n");
            $executed = $this->runner->runPending($this->migrations);

            if (empty($executed)) {
                $this->outputInfo("No migrations to apply.");
                return 0;
            }

            $this->outputSuccess("Applied " . count($executed) . " migration(s).\n");
            $this->logger->info("Database refreshed", ['migrations' => count($executed)]);
            return 0;
        } catch (\Exception $e) {
            $this->outputError("Database refresh failed: " . $e->getMessage());
            $this->logger->error("Database refresh failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }

    public function help(): string
    {
        return <<<'HELP'
Manage database migrations.

USAGE:
  php app.php migrate [SUBCOMMAND] [OPTIONS]

SUBCOMMANDS:
  up               Apply all pending migrations (default if no subcommand)
  down             Rollback the last batch of migrations
  status           Show current migration status and pending migrations
  refresh          Rollback all migrations and re-apply them

EXAMPLES:
  php app.php migrate up
  php app.php migrate down
  php app.php migrate status
  php app.php migrate refresh

DESCRIPTION:
  The migrate command manages database schema changes through versioned
  migrations. Each migration can be applied (up) or rolled back (down).

  Migrations are tracked in the schema_migrations table with batch numbers
  to enable atomic rollback of multiple migrations.

HELP;
    }

    /**
     * Output success message (green)
     */
    private function outputSuccess(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m";
    }

    /**
     * Output info message (white)
     */
    private function outputInfo(string $message): void
    {
        echo $message;
    }

    /**
     * Output warning message (yellow)
     */
    private function outputWarning(string $message): void
    {
        echo "\033[33m" . $message . "\033[0m";
    }

    /**
     * Output error message (red)
     */
    private function outputError(string $message): void
    {
        fwrite(STDERR, "\033[31m" . $message . "\033[0m\n");
    }
}
