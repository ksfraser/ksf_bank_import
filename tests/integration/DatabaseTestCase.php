<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Database\DatabaseFactory;

abstract class DatabaseTestCase extends TestCase
{
    protected static $pdo;

    public static function setUpBeforeClass(): void
    {
        // The repository under test reads through FrontAccounting's global
        // db_query() / db_fetch(). In the test environment those are the
        // famock stubs, which never touch a database, so the assertions can
        // only be made against a real FrontAccounting install that has the
        // bi_transactions / bi_statements tables (sql/update.sql).
        //
        // The tests live in the integration suite (phpunit.integration.xml),
        // where the skip is visible instead of hiding in the default run.
        if (!self::faDatabaseLayerAvailable()) {
            self::markTestSkipped(
                'Needs a live FrontAccounting install: the repository reads through FA\'s global '
                . 'db_query() / db_fetch(), and in the test environment those are the famock '
                . 'stubs, which never reach a database. Run this suite inside an FA install '
                . '(with the bi_transactions / bi_statements tables from sql/update.sql) - see '
                . 'phpunit.integration.xml.'
            );
        }

        try {
            self::$pdo = DatabaseFactory::getConnection();
        } catch (\Throwable $e) {
            self::markTestSkipped(
                'The bi_transactions / bi_statements test database is not reachable: '
                . $e->getMessage()
                . '. The connection is configured with DB_HOST / DB_NAME / DB_USER / DB_PASS '
                . '(default: localhost / fa_bank_import / root / no password) and the schema '
                . 'comes from sql/update.sql. Run this suite with: '
                . 'phpunit -c phpunit.integration.xml'
            );
        }
        self::$pdo->beginTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanTestData();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
        DatabaseFactory::closeConnection();
    }

    /**
     * Is FA's database layer wired up to a real database?
     *
     * The famock stubs record the last SQL in $GLOBALS['__fa_last_sql'] and
     * never query anything; a real FA install does not.
     *
     * @return bool
     */
    protected static function faDatabaseLayerAvailable(): bool
    {
        if (!function_exists('db_query')) {
            return false;
        }

        // famock's db_* stubs are loaded by the test bootstrap; a real FA
        // install does not load them.
        return !isset($GLOBALS['__fa_db_stubs_loaded']);
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
        // The table carries the company prefix (TB_PREF), like every other
        // table of the module.
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';

        $sql = "INSERT INTO {$prefix}bi_transactions (
            amount, valueTimestamp, memo, transactionDC, status
        ) VALUES (
            :amount, :valueTimestamp, :memo, :transactionDC, :status
        )";

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($data);

        return (int)self::$pdo->lastInsertId();
    }
}