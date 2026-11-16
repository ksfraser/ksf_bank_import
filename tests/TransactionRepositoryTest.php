<?php

namespace Tests\Unit;

/**
 * TransactionRepository Integration Tests
 * 
 * NOTE: This test file validates TransactionRepository methods per the requirements specification
 * found in Project Documents/MDs/REGRESSION_TESTING_SESSION_2025-11-14.md
 * 
 * RELATIONSHIP TO REQUIREMENTS:
 * =============================
 * The following methods MUST exist per regression testing requirements (NOT deprecated):
 * - findById($id): ?array - Returns single transaction or null
 * - findByStatus($status): array - Returns transactions filtered by status
 * - save($data): bool - Inserts new transaction
 * - update($id, $data): bool - Updates transaction fields dynamically
 * 
 * IMPLEMENTATION STATUS:
 * =====================
 * ✅ All four methods implemented in src/Ksfraser/FaBankImport/Repositories/TransactionRepository.php
 * ✅ Methods follow parameterized queries per regression spec
 * ✅ Edge cases handled: empty arrays, ID=0, empty data, type preservation
 * ✅ Dependency injection properly configured in test setUp()
 * 
 * These tests verify the integration between the repository and FA mock functions.
 * The methods are NOT deprecated - they are intentional per requirements.
 * 
 * FOR DEVELOPERS:
 * - To run: php vendor/bin/phpunit tests/TransactionRepositoryTest.php
 * - Requires: tests/helpers/fa_functions.php mock functions loaded
 * - Status: ACTIVE - These methods should exist and be maintained
 */

// Include FA mock functions for isolated unit testing
require_once __DIR__ . '/helpers/fa_functions.php';

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;
use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;

class TransactionRepositoryTest extends TestCase
{
    private $repository;

    protected function setUp(): void
    {
        $queryBuilder = new TransactionQueryBuilder();
        $this->repository = new TransactionRepository($queryBuilder);
    }

    public function testFindByIdReturnsNullWhenNotFound()
    {
        $result = $this->repository->findById(999999);
        $this->assertNull($result);
    }

    public function testFindByStatusReturnsEmptyArrayWhenNoResults()
    {
        $result = $this->repository->findByStatus('nonexistent');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testUpdateReturnsTrueOnSuccess()
    {
        $data = [
            'status' => 'processed',
            'transactionDC' => 'C'
        ];

        $result = $this->repository->update(1, $data);
        $this->assertTrue($result);
    }

    public function testSaveReturnsTrueOnSuccess()
    {
        $transaction = [
            'amount' => 100.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test transaction',
            'status' => 'pending'
        ];

        $result = $this->repository->save($transaction);
        $this->assertTrue($result);
    }
}
