<?php

namespace Ksfraser\FaBankImport\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repositories\BiTransactionRepository;
use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;

class BiTransactionRepositoryTest extends TestCase
{
    private BiTransactionRepository $repository;
    private array $validTransactionData;

    protected function setUp(): void
    {
        // Create repository with mock/stub database
        $this->repository = new BiTransactionRepository();
        
        $this->validTransactionData = [
            'id' => 1,
            'smtId' => 10,
            'valueTimestamp' => '2026-05-18',
            'entryTimestamp' => '2026-05-18 10:30:00',
            'account' => '1000',
            'accountName' => 'Checking',
            'transactionType' => 'DEBIT',
            'transactionCode' => 'CHK001',
            'transactionCodeDesc' => 'Check',
            'transactionDC' => 'D',
            'transactionAmount' => 1000.00,
            'transactionTitle' => 'Payment',
            'status' => 'PENDING',
            'matchinfo' => null,
            'faTransType' => null,
            'faTransNo' => null,
            'fitid' => 'FIT001',
            'acctid' => 'ACC001',
            'merchant' => 'Vendor A',
            'category' => 'OFFICE',
            'sic' => '5411',
            'memo' => 'Test',
            'checknumber' => '001',
            'matched' => false,
            'created' => false,
            'gPartner' => null,
            'gOption' => null,
        ];
    }

    /**
     * Test can find transaction by ID
     */
    public function testCanFindTransactionById(): void
    {
        $transaction = $this->repository->findById(1);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertEquals(1, $transaction->getId());
    }

    /**
     * Test throws exception when transaction not found
     */
    public function testThrowsExceptionWhenTransactionNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->repository->findById(99999);
    }

    /**
     * Test find by ID or null returns null
     */
    public function testFindByIdOrNullReturnsNull(): void
    {
        $transaction = $this->repository->findByIdOrNull(99999);
        $this->assertNull($transaction);
    }

    /**
     * Test find by ID or null returns entity
     */
    public function testFindByIdOrNullReturnsEntity(): void
    {
        $transaction = $this->repository->findByIdOrNull(1);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertEquals(1, $transaction->getId());
    }

    /**
     * Test find all returns collection
     */
    public function testFindAllReturnsCollection(): void
    {
        $collection = $this->repository->findAll();
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
        $this->assertGreater(0, count($collection));
    }

    /**
     * Test find all respects limit
     */
    public function testFindAllRespectsLimit(): void
    {
        $collection = $this->repository->findAll(limit: 5);
        
        $this->assertLessThanOrEqual(5, count($collection));
    }

    /**
     * Test find by criteria
     */
    public function testCanFindByCriteria(): void
    {
        $criteria = ['smtId' => 10];
        $collection = $this->repository->findBy($criteria);
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test find by IDs returns collection
     */
    public function testFindByIdsReturnsCollection(): void
    {
        $ids = [1, 2, 3];
        $collection = $this->repository->findByIds($ids);
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test count returns integer
     */
    public function testCountReturnsInteger(): void
    {
        $count = $this->repository->count();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test count with criteria
     */
    public function testCountWithCriteria(): void
    {
        $criteria = ['status' => 'PENDING'];
        $count = $this->repository->count($criteria);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test can save transaction
     */
    public function testCanSaveTransaction(): void
    {
        $transaction = BiTransaction::create($this->validTransactionData);
        $id = $this->repository->save($transaction);
        
        $this->assertIsInt($id);
        $this->assertGreater(0, $id);
    }

    /**
     * Test delete transaction
     */
    public function testCanDeleteTransaction(): void
    {
        $result = $this->repository->delete(1);
        
        $this->assertTrue(is_bool($result));
    }

    /**
     * Test delete multiple transactions
     */
    public function testCanDeleteMultiple(): void
    {
        $ids = [1, 2];
        $count = $this->repository->deleteMultiple($ids);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test exists returns boolean
     */
    public function testExistsReturnsBoolean(): void
    {
        $exists = $this->repository->exists(1);
        
        $this->assertIsBool($exists);
    }

    /**
     * Test exists returns false for non-existent ID
     */
    public function testExistsReturnsFalseForNonExistentId(): void
    {
        $exists = $this->repository->exists(99999);
        
        $this->assertFalse($exists);
    }

    /**
     * Test find by statement ID
     */
    public function testCanFindByStatementId(): void
    {
        $collection = $this->repository->findByStatementId(10);
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test find matched transactions
     */
    public function testCanFindMatched(): void
    {
        $collection = $this->repository->findMatched();
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test find unmatched transactions
     */
    public function testCanFindUnmatched(): void
    {
        $collection = $this->repository->findUnmatched();
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test find by transaction code
     */
    public function testCanFindByTransactionCode(): void
    {
        $collection = $this->repository->findByTransactionCode('CHK001');
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test find by amount range
     */
    public function testCanFindByAmountRange(): void
    {
        $collection = $this->repository->findByAmountRange(100.00, 5000.00);
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test get summary stats
     */
    public function testCanGetSummaryStats(): void
    {
        $stats = $this->repository->getSummaryStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('count', $stats);
        $this->assertArrayHasKey('sum', $stats);
        $this->assertArrayHasKey('avg', $stats);
    }

    /**
     * Test summary stats with criteria
     */
    public function testSummaryStatsWithCriteria(): void
    {
        $criteria = ['matched' => true];
        $stats = $this->repository->getSummaryStats($criteria);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('count', $stats);
    }

    /**
     * Test repository returns entities not DTOs from find methods
     */
    public function testRepositoryReturnsBiTransactionNotDTO(): void
    {
        $transaction = $this->repository->findById(1);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertNotInstanceOf(BiTransactionDTO::class, $transaction);
    }

    /**
     * Test repository collection contains entities
     */
    public function testRepositoryCollectionContainsBiTransactions(): void
    {
        $collection = $this->repository->findAll();
        
        if (count($collection) > 0) {
            $first = $collection->first();
            $this->assertInstanceOf(BiTransaction::class, $first);
        }
    }

    /**
     * Test can find by multiple criteria
     */
    public function testCanFindByMultipleCriteria(): void
    {
        $criteria = ['smtId' => 10, 'status' => 'PENDING'];
        $collection = $this->repository->findBy($criteria);
        
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'count'));
    }

    /**
     * Test pagination with offset
     */
    public function testPaginationWithOffset(): void
    {
        $page1 = $this->repository->findAll(limit: 10, offset: 0);
        $page2 = $this->repository->findAll(limit: 10, offset: 10);
        
        // Different pages should potentially have different items
        $this->assertIsObject($page1);
        $this->assertIsObject($page2);
    }
}
