<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;
use Ksfraser\FaBankImport\Infrastructure\Config\EnvironmentConfig;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationRunner;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;
    protected EnvironmentConfig $config;
    protected MigrationRunner $migrationRunner;
    protected array $executedMigrations = [];

    public static function setUpBeforeClass(): void
    {
        // Connection setup happens in setUp for modern approach
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new EnvironmentConfig('test');
        $creds = $this->config->getDatabaseCredentials();

        try {
            $this->pdo = new PDO(
                $creds['dsn'],
                $creds['user'],
                $creds['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            $this->markTestSkipped(
                "Could not connect to test database: " . $e->getMessage()
            );
        }

        $this->migrationRunner = new MigrationRunner($this->pdo);
        $this->seedTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanTestData();

        // Rollback transaction to restore database to pre-test state
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Legacy compatibility
    }

    protected function seedTestData(): void
    {
        // Override in child classes to seed specific test data
    }

    protected function cleanTestData(): void
    {
        // Override in child classes to clean specific test data
    }

    protected function createTestTransaction(array $data): int
    {
        $sql = "INSERT INTO bi_transactions (
            amount, valueTimestamp, memo, transactionDC, status
        ) VALUES (
            :amount, :valueTimestamp, :memo, :transactionDC, :status
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Run migrations for this test
     *
     * @param array<string> $migrationClasses Migration class names to execute
     * @return array Executed migration names
     */
    protected function runMigrations(array $migrationClasses): array
    {
        $migrations = array_map(fn($class) => new $class(), $migrationClasses);
        $this->executedMigrations = $migrationClasses;
        return $this->migrationRunner->runPending($migrations);
    }

    /**
     * Clear a table completely
     *
     * @param string $tableName Table name
     */
    protected function clearTable(string $tableName): void
    {
        try {
            $this->pdo->exec("DELETE FROM {$tableName}");
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                "Failed to clear table {$tableName}: " . $e->getMessage()
            );
        }
    }

    /**
     * Get row count for a table
     *
     * @param string $tableName Table name
     * @return int Row count
     */
    protected function countRows(string $tableName): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$tableName}");
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Assert table has expected row count
     *
     * @param string $tableName Table name
     * @param int $expectedCount Expected row count
     * @param string $message Custom assertion message
     */
    protected function assertTableRowCount(
        string $tableName,
        int $expectedCount,
        string $message = ''
    ): void {
        $actual = $this->countRows($tableName);
        $this->assertEquals(
            $expectedCount,
            $actual,
            $message ?: "Table {$tableName} should have {$expectedCount} rows, got {$actual}"
        );
    }
}