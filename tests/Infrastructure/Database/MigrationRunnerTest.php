<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationRunner;
use Ksfraser\FaBankImport\Infrastructure\Database\Migration;
use Ksfraser\FaBankImport\Infrastructure\Database\MigrationException;
use PDO;

/**
 * Test Migration Implementation for testing
 */
class TestMigration implements Migration
{
    private string $name;
    private string $description;
    public bool $upCalled = false;
    public bool $downCalled = false;

    public function __construct(string $name, string $description = '')
    {
        $this->name = $name;
        $this->description = $description ?: "Test migration: $name";
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function up(PDO $pdo): void
    {
        $this->upCalled = true;
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_" . str_replace('_', '', $this->name) . " (id INT)");
    }

    public function down(PDO $pdo): void
    {
        $this->downCalled = true;
        $pdo->exec("DROP TABLE IF EXISTS test_" . str_replace('_', '', $this->name));
    }
}

/**
 * Tests for MigrationRunner
 * 
 * Verifies migration execution, tracking, and rollback functionality.
 * 
 * @author Kevin Fraser
 * @since 2.2.0
 */
class MigrationRunnerTest extends TestCase
{
    private PDO $pdo;
    private MigrationRunner $runner;

    protected function setUp(): void
    {
        // Use in-memory SQLite for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->runner = new MigrationRunner($this->pdo, 'schema_migrations');
    }

    public function testInitializesMigrationsTable(): void
    {
        // Check migrations table was created
        $result = $this->pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='schema_migrations'"
        );
        
        $this->assertNotFalse($result->fetch());
    }

    public function testRunPendingExecutesMigrations(): void
    {
        $migration1 = new TestMigration('20260416_001_first');
        $migration2 = new TestMigration('20260416_002_second');

        $executed = $this->runner->runPending([$migration1, $migration2]);

        $this->assertCount(2, $executed);
        $this->assertTrue($migration1->upCalled);
        $this->assertTrue($migration2->upCalled);
    }

    public function testRunPendingSkipsAlreadyExecuted(): void
    {
        $migration1 = new TestMigration('20260416_001_first');
        $migration2 = new TestMigration('20260416_002_second');

        // Run first time
        $this->runner->runPending([$migration1]);
        $this->assertTrue($migration1->upCalled);

        // Reset upCalled flag
        $migration1->upCalled = false;

        // Run again - should skip migration1
        $this->runner->runPending([$migration1, $migration2]);

        $this->assertFalse($migration1->upCalled); // Should not run again
        $this->assertTrue($migration2->upCalled);  // Should run
    }

    public function testRunPendingRecordsMigrations(): void
    {
        $migration = new TestMigration('20260416_001_first');
        $this->runner->runPending([$migration]);

        $result = $this->pdo->query(
            "SELECT migration FROM schema_migrations WHERE migration = '20260416_001_first'"
        );

        $this->assertNotFalse($result->fetch());
    }

    public function testGetExecutedReturnsMigrationNames(): void
    {
        $migration1 = new TestMigration('20260416_001_first');
        $migration2 = new TestMigration('20260416_002_second');

        $this->runner->runPending([$migration1, $migration2]);

        $executed = $this->runner->getExecuted();

        $this->assertCount(2, $executed);
        $this->assertContains('20260416_001_first', $executed);
        $this->assertContains('20260416_002_second', $executed);
    }

    public function testGetStatusReturnsAllMigrationStatus(): void
    {
        $migration1 = new TestMigration('20260416_001_first');
        $migration2 = new TestMigration('20260416_002_second');
        $migration3 = new TestMigration('20260416_003_third');

        // Run first two migrations
        $this->runner->runPending([$migration1, $migration2]);

        // Check status of all three
        $status = $this->runner->getStatus([$migration1, $migration2, $migration3]);

        $this->assertTrue($status['20260416_001_first']);
        $this->assertTrue($status['20260416_002_second']);
        $this->assertFalse($status['20260416_003_third']);
    }

    public function testRollbackLastRollsBackLatestBatch(): void
    {
        $migration1 = new TestMigration('20260416_001_first');
        $migration2 = new TestMigration('20260416_002_second');

        // Run migrations
        $this->runner->runPending([$migration1, $migration2]);

        // Rollback last batch
        $rolledBack = $this->runner->rollbackLast([
            '20260416_001_first' => $migration1,
            '20260416_002_second' => $migration2,
        ]);

        // Should rollback in reverse order
        $this->assertCount(2, $rolledBack);
        $this->assertTrue($migration2->downCalled);
        $this->assertTrue($migration1->downCalled);
    }

    public function testRollbackLastRemovesMigrationRecords(): void
    {
        $migration = new TestMigration('20260416_001_first');

        $this->runner->runPending([$migration]);
        $this->assertCount(1, $this->runner->getExecuted());

        $this->runner->rollbackLast(['20260416_001_first' => $migration]);
        $this->assertCount(0, $this->runner->getExecuted());
    }

    public function testRollbackWithNoMigrationsReturnsEmpty(): void
    {
        $rolledBack = $this->runner->rollbackLast([]);
        $this->assertEmpty($rolledBack);
    }

    public function testBatchTracking(): void
    {
        $migration1 = new TestMigration('20260416_001_first');
        $migration2 = new TestMigration('20260416_002_second');

        // Run batch 1
        $this->runner->runPending([$migration1]);

        // Run batch 2
        $migration2->upCalled = false;
        $this->runner->runPending([$migration2]);

        $result = $this->pdo->query(
            "SELECT DISTINCT batch FROM schema_migrations ORDER BY batch"
        );

        $batches = $result->fetchAll(PDO::FETCH_COLUMN);
        $this->assertEquals([1, 2], $batches);
    }

    public function testMigrationFailureThrows(): void
    {
        $failingMigration = new class implements Migration {
            public function name(): string { return 'failing'; }
            public function description(): string { return 'Fails'; }
            public function up(PDO $pdo): void { throw new \Exception('Intentional failure'); }
            public function down(PDO $pdo): void { }
        };

        $this->expectException(MigrationException::class);
        $this->runner->runPending([$failingMigration]);
    }

    public function testEmptyMigrationListReturnsEmpty(): void
    {
        $executed = $this->runner->runPending([]);
        $this->assertEmpty($executed);
    }

    public function testExecutedMigrationsAreOrdered(): void
    {
        $migrations = [
            new TestMigration('20260416_001_first'),
            new TestMigration('20260416_002_second'),
            new TestMigration('20260416_003_third'),
        ];

        $this->runner->runPending($migrations);

        $executed = $this->runner->getExecuted();
        $this->assertEquals([
            '20260416_001_first',
            '20260416_002_second',
            '20260416_003_third',
        ], $executed);
    }
}
