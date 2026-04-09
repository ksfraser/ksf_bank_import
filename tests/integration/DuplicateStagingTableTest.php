<?php

namespace Ksfraser\FaBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Integration Tests: Duplicate Transaction Staging Table
 *
 * Tests verify the bi_transactions_dupe table schema, constraints,
 * and migration mechanics following Test-Driven Development approach.
 *
 * Migrations are automatically executed during test setup via update.sql.
 *
 * @package Ksfraser\FaBankImport\Tests\Integration
 * @since 2026-04-08
 */
class DuplicateStagingTableTest extends TestCase
{
    /**
     * @var PDO Database connection
     */
    private static $pdo;

    /**
     * Set up database connection for test suite
     * Auto-runs migrations from update.sql
     */
    public static function setUpBeforeClass(): void
    {
        // Connect to test database
        $dsn = getenv('TEST_DB_DSN') ?: 'mysql:host=localhost;dbname=fa_test';
        $user = getenv('TEST_DB_USER') ?: 'root';
        $pass = getenv('TEST_DB_PASS') ?: '';

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            // Initialize migrator and run migrations
            TestDatabaseMigrator::init(self::$pdo);
            
            if (!TestDatabaseMigrator::runMigrations()) {
                self::markTestSkipped(
                    "Database migrations could not be executed.\n" .
                    "Check that update.sql exists and database is writable."
                );
            }

            // Verify migrations completed successfully
            if (!TestDatabaseMigrator::verifyMigration()) {
                self::markTestSkipped(
                    "Migration verification failed. Required tables not found.\n" .
                    "Required tables: 0_bi_transactions_dupe, 0_bi_transactions_dupe_audit"
                );
            }
        } catch (\PDOException $e) {
            self::markTestSkipped(
                "Database connection failed. Skipping database tests.\n\n" .
                "To run database integration tests:\n" .
                "1. Ensure MySQL/MariaDB is running\n" .
                "2. Create test database: CREATE DATABASE fa_test;\n" .
                "3. Set environment variables:\n" .
                "   - TEST_DB_DSN\n" .
                "   - TEST_DB_USER\n" .
                "   - TEST_DB_PASS\n\n" .
                "Connection attempted: $dsn\n" .
                "Error: " . $e->getMessage()
            );
        }
    }

    /**
     * Test 1: Table exists with all required columns
     *
     * @test
     * @return void
     */
    public function test_table_exists_with_required_columns(): void
    {
        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe' AND TABLE_SCHEMA = DATABASE()";
        
        $result = self::$pdo->query($query);
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);

        $required = [
            'duplicate_id', 'transaction_code', 'trans_date', 'amount',
            'counterparty_name', 'description', 'reference_number',
            'bank_account_id', 'partner_type', 'bank_code',
            'decision_status', 'decided_by', 'decided_at', 'reason', 'notes'
        ];

        foreach ($required as $column) {
            $this->assertContains(
                $column,
                $columns,
                "Column '$column' not found in 0_bi_transactions_dupe table"
            );
        }
    }

    /**
     * Test 2: Audit columns present for tracking decisions
     *
     * @test
     * @return void
     */
    public function test_audit_columns_present(): void
    {
        $query = "SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe' 
                  AND COLUMN_NAME IN ('decided_by', 'decided_at', 'reason', 'notes')";
        
        $result = self::$pdo->query($query);
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(4, $columns, "All 4 audit columns must exist");

        // Verify column types
        $columnMap = array_column($columns, 'COLUMN_TYPE', 'COLUMN_NAME');
        
        $this->assertStringContainsString('varchar', strtolower($columnMap['decided_by']));
        $this->assertStringContainsString('datetime', strtolower($columnMap['decided_at']));
        // 'reason' can be text or varchar
        $this->assertTrue(
            strpos(strtolower($columnMap['reason']), 'text') !== false || 
            strpos(strtolower($columnMap['reason']), 'varchar') !== false
        );
        // 'notes' can be text or varchar
        $this->assertTrue(
            strpos(strtolower($columnMap['notes']), 'text') !== false || 
            strpos(strtolower($columnMap['notes']), 'varchar') !== false
        );
    }

    /**
     * Test 3: Unique constraint on (transaction_code, duplicate_id)
     *
     * @test
     * @return void
     */
    public function test_unique_constraint_on_transaction_duplicate(): void
    {
        // First, check that the constraint exists
        $query = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe' 
                  AND CONSTRAINT_TYPE = 'UNIQUE'";
        
        $result = self::$pdo->query($query);
        $constraints = $result->fetchAll(PDO::FETCH_COLUMN);

        $this->assertNotEmpty($constraints, "Unique constraint should exist on 0_bi_transactions_dupe");

        // Try inserting duplicate rows - should fail on second insert
        try {
            self::$pdo->beginTransaction();
            
            // Insert first record
            $insert = "INSERT INTO 0_bi_transactions_dupe 
                       (transaction_code, matched_to_code, trans_date, amount, counterparty_name, bank_account_id)
                       VALUES (?, ?, NOW(), ?, ?, ?)";
            $stmt = self::$pdo->prepare($insert);
            $stmt->execute(['CODE001', 'DUP001', 1000.00, 'Test Counterparty', 1]);

            // Try inserting duplicate - should violate unique constraint
            $stmt->execute(['CODE001', 'DUP001', 1000.00, 'Test Counterparty', 1]);
            
            self::$pdo->rollBack();
            $this->fail("Unique constraint violation should have been thrown");
        } catch (\PDOException $e) {
            self::$pdo->rollBack();
            // MySQL returns duplicate entry error: SQLSTATE[23000] = Integrity constraint violation
            $this->assertStringContainsString('23000', $e->getCode());
        }
    }

    /**
     * Test 4: Proper indexing for query performance
     *
     * @test
     * @return void
     */
    public function test_required_indexes_exist(): void
    {
        $query = "SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe'";
        
        $result = self::$pdo->query($query);
        $indexes = $result->fetchAll(PDO::FETCH_COLUMN);

        $required = ['PRIMARY', 'idx_transaction_code', 'idx_trans_date', 'idx_decision_status'];
        
        foreach ($required as $index) {
            $this->assertContains(
                $index,
                $indexes,
                "Index '$index' not found on 0_bi_transactions_dupe table"
            );
        }
    }

    /**
     * Test 5: Migration script is idempotent
     *
     * @test
     * @return void
     */
    public function test_migration_is_idempotent(): void
    {
        $migrationFile = __DIR__ . '/../../sql/migrations/001_create_bi_transactions_dupe_table.sql';
        $this->assertFileExists($migrationFile, "Migration file not found at $migrationFile");

        $migration = file_get_contents($migrationFile);
        
        // Verify idempotency - should contain IF NOT EXISTS
        $this->assertStringContainsString(
            'IF NOT EXISTS',
            $migration,
            "Migration should use IF NOT EXISTS for idempotency"
        );
    }

    /**
     * Test 6: Default values set correctly
     *
     * @test
     * @return void
     */
    public function test_default_values_set_correctly(): void
    {
        $query = "SELECT COLUMN_NAME, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe' 
                  AND COLUMN_DEFAULT IS NOT NULL";
        
        $result = self::$pdo->query($query);
        $defaults = $result->fetchAll(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(0, count($defaults), "Table should have default values");
        
        // Verify decision_status has a default
        $decisionDefaults = array_filter(
            $defaults,
            fn($col) => $col['COLUMN_NAME'] === 'decision_status'
        );
        
        $this->assertGreaterThan(0, count($decisionDefaults), "decision_status should have a default value");
    }

    /**
     * Test 7: Decision status column uses enum for strict validation
     *
     * @test
     * @return void
     */
    public function test_decision_status_enum_values(): void
    {
        $query = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe' 
                  AND COLUMN_NAME = 'decision_status'";
        
        $result = self::$pdo->query($query);
        $columnType = $result->fetchColumn();

        // Should be ENUM type with valid statuses
        $this->assertStringContainsString('enum', strtolower($columnType));
        
        // Expected statuses
        $expectedStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE'];
        foreach ($expectedStatuses as $status) {
            $this->assertStringContainsString($status, $columnType);
        }
    }

    /**
     * Test 8: Can insert and retrieve duplicate transaction record
     *
     * @test
     * @return void
     */
    public function test_can_insert_and_retrieve_duplicate_record(): void
    {
        try {
            self::$pdo->beginTransaction();

            $insert = "INSERT INTO 0_bi_transactions_dupe 
                       (transaction_code, trans_date, amount, 
                        counterparty_name, bank_account_id, decision_status)
                       VALUES (?, NOW(), ?, ?, ?, 'PENDING')";
            
            $stmt = self::$pdo->prepare($insert);
            $stmt->execute(['TEST_CODE', 5000.00, 'Test Company', 1]);

            // Retrieve inserted record
            $select = "SELECT * FROM 0_bi_transactions_dupe 
                       WHERE transaction_code = ?";
            $stmt = self::$pdo->prepare($select);
            $stmt->execute(['TEST_CODE']);
            
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            self::$pdo->rollBack();

            $this->assertNotEmpty($record, "Record should be retrieved");
            $this->assertEquals('TEST_CODE', $record['transaction_code']);
            $this->assertEquals(5000.00, $record['amount']);
            $this->assertEquals('PENDING', $record['decision_status']);
        } catch (\Exception $e) {
            self::$pdo->rollBack();
            $this->fail("Failed to insert/retrieve: " . $e->getMessage());
        }
    }

    /**
     * Test 9: Foreign key constraints (if applicable)
     *
     * @test
     * @return void
     */
    public function test_foreign_key_constraints(): void
    {
        // Check FK from audit table to main table
        $query = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                  WHERE TABLE_NAME = '0_bi_transactions_dupe_audit' 
                  AND CONSTRAINT_SCHEMA = DATABASE()";
        
        $result = self::$pdo->query($query);
        $fks = $result->fetchAll(PDO::FETCH_COLUMN);

        // Verify audit table has foreign key to main table
        $this->assertNotEmpty($fks, "Audit table should have foreign key to main table");
        $this->assertContains('fk_audit_duplicate', $fks, "Should have fk_audit_duplicate constraint");
    }

    /**
     * Tear down after tests
     */
    public static function tearDownAfterClass(): void
    {
        // Clean up test data if needed
        self::$pdo = null;
    }
}
