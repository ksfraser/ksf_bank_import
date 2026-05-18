<?php

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\BiTransactionService;
use Ksfraser\FaBankImport\Repositories\BiTransactionRepository;
use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;

class BiTransactionServiceTest extends TestCase
{
    private BiTransactionService $service;
    private BiTransactionRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new BiTransactionRepository();
        $this->service = new BiTransactionService($this->repository);
    }

    /**
     * Test service is properly injected with repository
     */
    public function testServiceHasRepositoryInjected(): void
    {
        $this->assertInstanceOf(BiTransactionService::class, $this->service);
    }

    /**
     * Test get transaction returns entity
     */
    public function testGetTransactionReturnsEntity(): void
    {
        $transaction = $this->service->getTransaction(1);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertEquals(1, $transaction->getId());
    }

    /**
     * Test get transaction throws exception if not found
     */
    public function testGetTransactionThrowsExceptionIfNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->service->getTransaction(99999);
    }

    /**
     * Test list all transactions with pagination
     */
    public function testListAllTransactionsPaginated(): void
    {
        $result = $this->service->listAllTransactions(page: 1, pageSize: 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('pageSize', $result);
        $this->assertArrayHasKey('pages', $result);
    }

    /**
     * Test list all transactions structure
     */
    public function testListAllTransactionsStructure(): void
    {
        $result = $this->service->listAllTransactions();
        
        $this->assertEquals(1, $result['page']);
        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['pageSize']);
        $this->assertIsInt($result['pages']);
        $this->assertIsObject($result['items']);
    }

    /**
     * Test list matched transactions
     */
    public function testListMatchedTransactions(): void
    {
        $result = $this->service->listMatchedTransactions(page: 1, pageSize: 10);
        
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * Test list unmatched transactions
     */
    public function testListUnmatchedTransactions(): void
    {
        $result = $this->service->listUnmatchedTransactions(page: 1, pageSize: 10);
        
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * Test toggle debit credit changes transaction
     */
    public function testToggleDebitCreditChangesTransaction(): void
    {
        $original = $this->service->getTransaction(1);
        $originalDC = $original->getTransactionDC();
        
        $toggled = $this->service->toggleDebitCredit(1);
        
        $this->assertNotEquals($originalDC, $toggled->getTransactionDC());
    }

    /**
     * Test mark as matched
     */
    public function testMarkAsMatched(): void
    {
        $transaction = $this->service->markAsMatched(1, matchinfo: 'INV-123');
        
        $this->assertTrue($transaction->isMatched());
    }

    /**
     * Test mark as created
     */
    public function testMarkAsCreated(): void
    {
        $transaction = $this->service->markAsCreated(1);
        
        $this->assertTrue($transaction->isCreated());
    }

    /**
     * Test link to FA transaction
     */
    public function testLinkToFATransaction(): void
    {
        $transaction = $this->service->linkToFATransaction(1, faTransNo: 999, faTransType: 1);
        
        $this->assertEquals(999, $transaction->getFaTransNo());
        $this->assertEquals(1, $transaction->getFaTransType());
    }

    /**
     * Test set partner info
     */
    public function testSetPartnerInfo(): void
    {
        $transaction = $this->service->setPartnerInfo(1, partnerId: 'CUST001', partnerOption: 'CREDIT');
        
        $this->assertEquals('CUST001', $transaction->getGPartner());
        $this->assertEquals('CREDIT', $transaction->getGOption());
    }

    /**
     * Test get debit transactions
     */
    public function testGetDebitTransactions(): void
    {
        $result = $this->service->getDebitTransactions(page: 1, pageSize: 10);
        
        $this->assertArrayHasKey('items', $result);
    }

    /**
     * Test get credit transactions
     */
    public function testGetCreditTransactions(): void
    {
        $result = $this->service->getCreditTransactions(page: 1, pageSize: 10);
        
        $this->assertArrayHasKey('items', $result);
    }

    /**
     * Test search transactions by code
     */
    public function testSearchByCode(): void
    {
        $result = $this->service->searchByCode(code: 'CHK001', page: 1, pageSize: 10);
        
        $this->assertArrayHasKey('items', $result);
    }

    /**
     * Test find transactions by amount range
     */
    public function testFindByAmountRange(): void
    {
        $result = $this->service->findByAmountRange(min: 100.00, max: 5000.00, page: 1, pageSize: 10);
        
        $this->assertArrayHasKey('items', $result);
    }

    /**
     * Test get transaction statistics
     */
    public function testGetTransactionStatistics(): void
    {
        $stats = $this->service->getTransactionStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalCount', $stats);
        $this->assertArrayHasKey('matchedCount', $stats);
        $this->assertArrayHasKey('unmatchedCount', $stats);
        $this->assertArrayHasKey('totalAmount', $stats);
        $this->assertArrayHasKey('averageAmount', $stats);
        $this->assertArrayHasKey('minAmount', $stats);
        $this->assertArrayHasKey('maxAmount', $stats);
    }

    /**
     * Test save transaction persists to repository
     */
    public function testSaveTransactionPeristsToRepository(): void
    {
        $transaction = BiTransaction::create([
            'transactionCode' => 'NEW001',
            'transactionDC' => 'D',
            'transactionAmount' => 500.00,
        ]);
        
        $saved = $this->service->saveTransaction($transaction);
        
        $this->assertIsInt($saved);
        $this->assertGreater(0, $saved);
    }

    /**
     * Test delete transaction
     */
    public function testDeleteTransaction(): void
    {
        $result = $this->service->deleteTransaction(1);
        
        $this->assertTrue($result);
    }

    /**
     * Test get matched percentage
     */
    public function testGetMatchedPercentage(): void
    {
        $percentage = $this->service->getMatchedPercentage();
        
        $this->assertIsFloat($percentage);
        $this->assertGreaterThanOrEqual(0.0, $percentage);
        $this->assertLessThanOrEqual(100.0, $percentage);
    }

    /**
     * Test get summary by statement
     */
    public function testGetSummaryByStatement(): void
    {
        $summary = $this->service->getSummaryByStatement(smtId: 10);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('count', $summary);
        $this->assertArrayHasKey('sum', $summary);
    }

    /**
     * Test bulk mark as matched
     */
    public function testBulkMarkAsMatched(): void
    {
        $ids = [1, 2, 3];
        $result = $this->service->bulkMarkAsMatched($ids);
        
        $this->assertIsArray($result);
        $this->assertTrue(is_bool($result[0]));
    }

    /**
     * Test bulk delete
     */
    public function testBulkDelete(): void
    {
        $ids = [1, 2, 3];
        $count = $this->service->bulkDelete($ids);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test convert to DTO
     */
    public function testConvertToDTO(): void
    {
        $transaction = $this->service->getTransaction(1);
        $dto = $this->service->convertToDTO($transaction);
        
        $this->assertInstanceOf(BiTransactionDTO::class, $dto);
    }

    /**
     * Test can convert entities to DTOs
     */
    public function testConvertEntitiesToDTOs(): void
    {
        $collection = $this->repository->findAll(limit: 5);
        $dtos = $this->service->convertCollectionToDTOs($collection);
        
        $this->assertIsArray($dtos);
        if (!empty($dtos)) {
            $this->assertInstanceOf(BiTransactionDTO::class, $dtos[0]);
        }
    }

    /**
     * Test service enforces immutability - returned transactions are new instances
     */
    public function testServiceEnforcesImmutability(): void
    {
        $t1 = $this->service->getTransaction(1);
        $t2 = $this->service->markAsMatched(1);
        
        // Verify both exist but are different
        $this->assertNotSame($t1, $t2);
        $this->assertFalse($t1->isMatched());
        $this->assertTrue($t2->isMatched());
    }

    /**
     * Test service method chaining returns persisted changes
     */
    public function testServicePersistsBehaviorChangeSequence(): void
    {
        $original = $this->service->getTransaction(2);
        $dc1 = $original->getTransactionDC();
        
        $toggled1 = $this->service->toggleDebitCredit(2);
        $toggled2 = $this->service->toggleDebitCredit(2);
        
        // Two toggles should return to original
        $this->assertEquals($dc1, $toggled2->getTransactionDC());
    }
}
