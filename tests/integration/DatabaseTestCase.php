<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Database\DatabaseFactory;

abstract class DatabaseTestCase extends TestCase
{
    protected static $pdo;

    public static function setUpBeforeClass(): void
    {
        // Graceful skip when the integration database is unavailable so the
        // unit suite stays green on machines without the FA test schema.
        try {
            self::$pdo = DatabaseFactory::getConnection();
        } catch (\Throwable $e) {
            self::markTestSkipped('Integration database unavailable: ' . $e->getMessage());
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