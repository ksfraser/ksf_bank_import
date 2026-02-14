<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Database\DatabaseFactory;

abstract class DatabaseTestCase extends TestCase
{
    protected static $pdo;
    protected static $usingSqliteFallback = false;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = DatabaseFactory::getConnection();
        } catch (\Throwable $e) {
            self::$pdo = new \PDO('sqlite::memory:');
            self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$usingSqliteFallback = true;
            self::createFallbackSchema();
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
        if (self::$pdo instanceof \PDO && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }

        if (!self::$usingSqliteFallback) {
            DatabaseFactory::closeConnection();
        }

        self::$pdo = null;
        self::$usingSqliteFallback = false;
    }

    protected static function createFallbackSchema(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS bi_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                amount REAL,
                transactionAmount REAL,
                valueTimestamp TEXT,
                memo TEXT,
                transactionTitle TEXT,
                transactionDC TEXT,
                status TEXT,
                fa_trans_no INTEGER DEFAULT 0,
                fa_trans_type INTEGER DEFAULT 0,
                matched INTEGER DEFAULT 0,
                created INTEGER DEFAULT 0,
                g_partner TEXT DEFAULT '',
                g_option TEXT DEFAULT '',
                account TEXT DEFAULT '',
                smt_id INTEGER DEFAULT 0
            )
        ";

        self::$pdo->exec($sql);
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

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($data);

        return (int)self::$pdo->lastInsertId();
    }
}