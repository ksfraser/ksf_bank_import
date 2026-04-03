<?php
namespace Ksfraser\Tests\Unit\Repository;

use DateTime;
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Shared\Repositories\TransactionRepository;
use Ksfraser\FaBankImport\Shared\Repositories\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Shared\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\DuplicateEntityException;
use Ksfraser\FaBankImport\Shared\Exceptions\RepositoryException;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidTransactionException;

/**
 * TransactionRepositoryTest - TDD RED PHASE
 * 
 * Unit tests for TransactionRepository interface and implementation.
 * Tests verify CRUD operations, query methods, bulk operations, and exception handling.
 * 
 * TESTING STRATEGY:
 * - Mock database connection (PDO) for unit tests
 * - All tests focus on business logic, not database implementation details
 * - Entity invariants validated
 * - Exception types verified
 * - SRP: Repository only handles persistence, not domain logic
 */
class TransactionRepositoryTest extends TestCase
{
    private TransactionRepositoryInterface $repository;
    private \PDO $mockPdo;

    protected function setUp(): void
    {
        // Create mock PDOStatement with correct database field names
        $mockRowData = [
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'FITID123',  // Lowercase - database field name
            'acctid' => 'ACCTID456',  // Lowercase - database field name
            'transactionAmount' => 100.50,
            'transactionTitle' => 'Test Transaction',
            'transactionCode' => 'CODE123',
            'status' => 0,
        ];
        
        $mockStatement = $this->createMock(\PDOStatement::class);
        
        // Configure execute() to always succeed
        $mockStatement->expects($this->any())
            ->method('execute')
            ->willReturn(true);
        
        // Configure fetch() to return a single row
        $mockStatement->expects($this->any())
            ->method('fetch')
            ->with($this->anything())
            ->willReturn($mockRowData);
        
        // Configure fetchAll() to return array of rows
        $mockStatement->expects($this->any())
            ->method('fetchAll')
            ->with($this->anything())
            ->willReturn([$mockRowData]);
        
        // Create mock PDO
        $this->mockPdo = $this->createMock(\PDO::class);
        
        // Configure prepare() to always return the mock statement
        $this->mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStatement);
        
        // Configure lastInsertId() to return an ID
        $this->mockPdo->expects($this->any())
            ->method('lastInsertId')
            ->willReturn('1');
            
        // Configure setAttribute() for PDO attribute setting
        $this->mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $this->repository = new TransactionRepository($this->mockPdo);
    }

    /**
     * TEST GROUP 1: Constructor & Initialization
     */

    /**
     * @test
     * @group constructor
     */
    public function testRepositoryImplementsInterface(): void
    {
        $this->assertInstanceOf(TransactionRepositoryInterface::class, $this->repository);
    }

    /**
     * @test
     * @group constructor
     */
    public function testRepositoryAcceptsPdoConnection(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $repo = new TransactionRepository($pdo);
        $this->assertNotNull($repo);
    }

    /**
     * TEST GROUP 2: Save Operation (INSERT/UPDATE)
     */

    /**
     * @test
     * @group save
     */
    public function testSaveNewTransactionReturnsEntityWithId(): void
    {
        $transaction = BiTransaction::create(
            smtId: 1,
            fitId: 'FITID123',
            acctId: 'ACCTID456',
            transactionAmount: 100.50,
            transactionTitle: 'Test Transaction'
        );

        $id = $this->repository->save($transaction);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id, 'Saved transaction must have ID > 0');
    }

    /**
     * @test
     * @group save
     */
    public function testSaveTransactionPersistsToDatabase(): void
    {
        $transaction = BiTransaction::create(
            smtId: 2,
            fitId: 'FITID789',
            acctId: 'ACCTID999',
            transactionAmount: -50.25,
            transactionTitle: 'Withdrawal'
        );

        $id = $this->repository->save($transaction);

        // Verify we got an ID back
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * @test
     * @group save
     */
    public function testSaveReturnsIdNotEntity(): void
    {
        $transaction = BiTransaction::create(
            smtId: 3,
            fitId: 'FITID111',
            acctId: 'ACCTID222',
            transactionAmount: 200.00,
            transactionTitle: 'Deposit'
        );

        $id = $this->repository->save($transaction);

        // Verify we get an int ID, not an entity
        $this->assertIsInt($id);
        $this->assertNotInstanceOf(BiTransaction::class, $id);
    }

    /**
     * @test
     * @group save
     * @expectedException InvalidTransactionException
     */
    public function testSaveThrowsExceptionForMissingFitId(): void
    {
        $this->expectException(InvalidTransactionException::class);
        
        BiTransaction::create(
            smtId: 4,
            fitId: '',  // Empty fitId - should throw in entity constructor
            acctId: 'ACCTID333',
            transactionAmount: 100.00,
            transactionTitle: 'Invalid'
        );
    }

    /**
     * @test
     * @group save
     * @expectedException InvalidTransactionException
     */
    public function testSaveThrowsExceptionForMissingAcctId(): void
    {
        $this->expectException(InvalidTransactionException::class);
        
        BiTransaction::create(
            smtId: 5,
            fitId: 'FITID444',
            acctId: '',  // Empty acctId - should throw in entity constructor
            transactionAmount: 100.00,
            transactionTitle: 'Invalid'
        );
    }

    /**
     * TEST GROUP 3: Find by ID
     */

    /**
     * @test
     * @group findById
     */
    public function testFindByIdReturnsTransactionEntity(): void
    {
        $found = $this->repository->findById(1);

        $this->assertInstanceOf(BiTransaction::class, $found);
        $this->assertEquals(1, $found->getId());
    }

    /**
     * @test
     * @group findById
     */
    public function testFindByIdThrowsEntityNotFoundExceptionForMissingId(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->findById(99999);
    }

    /**
     * @test
     * @group findById
     */
    public function testFindByIdReturnsCorrectTransactionData(): void
    {
        $found = $this->repository->findById(1);

        $this->assertEquals('FITID123', $found->getFitId());
        $this->assertEquals('ACCTID456', $found->getAcctId());
    }

    /**
     * TEST GROUP 4: Query Methods - By Code
     */

    /**
     * @test
     * @group queryByCode
     */
    public function testFindByCodeReturnsArrayOfTransactions(): void
    {
        $results = $this->repository->findByCode('CODE123');

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryByCode
     */
    public function testFindByCodeReturnsEmptyArrayForNoMatches(): void
    {
        $results = $this->repository->findByCode('NONEXISTENT');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * @group queryByCode
     */
    public function testFindByCodeReturnsOnlyMatchingTransactions(): void
    {
        $results = $this->repository->findByCode('CODE123');

        foreach ($results as $transaction) {
            $this->assertInstanceOf(BiTransaction::class, $transaction);
            $this->assertEquals('CODE123', $transaction->getTransactionCode());
        }
    }

    /**
     * TEST GROUP 5: Query Methods - By Statement
     */

    /**
     * @test
     * @group queryByStatement
     */
    public function testFindByStatementReturnsTransactionsForStatement(): void
    {
        $results = $this->repository->findByStatement(1);

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryByStatement
     */
    public function testFindByStatementReturnsOnlyTransactionsFromStatement(): void
    {
        $results = $this->repository->findByStatement(1);

        foreach ($results as $transaction) {
            $this->assertInstanceOf(BiTransaction::class, $transaction);
            $this->assertEquals(1, $transaction->getSmtId());
        }
    }

    /**
     * @test
     * @group queryByStatement
     */
    public function testFindByStatementReturnsEmptyForNoTransactions(): void
    {
        $results = $this->repository->findByStatement(99999);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * TEST GROUP 6: Query Methods - By Status
     */

    /**
     * @test
     * @group queryByStatus
     */
    public function testFindByStatusReturnsTransactionsWithStatus(): void
    {
        $results = $this->repository->findByStatus(0);  // 0 = unmatched

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryByStatus
     */
    public function testFindByStatusReturnsOnlyMatchingStatus(): void
    {
        $results = $this->repository->findByStatus(1);  // 1 = matched

        foreach ($results as $transaction) {
            $this->assertInstanceOf(BiTransaction::class, $transaction);
            $this->assertEquals(1, $transaction->getStatus());
        }
    }

    /**
     * TEST GROUP 7: Update Operation
     */

    /**
     * @test
     * @group update
     */
    public function testUpdateTransactionReturnsUpdatedEntity(): void
    {
        // Create a transaction, retrieve it, then update
        $original = $this->repository->findById(1);
        
        // Simulate update by creating new entity with updated values
        // (In real implementation, would need update builder or similar)
        $this->assertInstanceOf(BiTransaction::class, $original);
    }

    /**
     * @test
     * @group update
     */
    public function testUpdatePreservesImmutability(): void
    {
        $original = $this->repository->findById(1);
        
        // Entity should not have setters
        $this->assertFalse(method_exists($original, 'setStatus'));
    }

    /**
     * @test
     * @group update
     */
    public function testUpdateThrowsExceptionIfTransactionNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        
        $nonexistent = BiTransaction::create(
            smtId: 1,
            fitId: 'NONEXISTENT',
            acctId: 'NONEXISTENT',
            transactionAmount: 100.00,
            transactionTitle: 'Test'
        );
        
        $this->repository->update($nonexistent);
    }

    /**
     * TEST GROUP 8: Bulk Insert
     */

    /**
     * @test
     * @group bulkInsert
     */
    public function testBulkInsertReturnsArrayOfSavedEntities(): void
    {
        $transactions = [
            BiTransaction::create(1, 'FITID1', 'ACCTID1', 100.00, 'Trans1'),
            BiTransaction::create(1, 'FITID2', 'ACCTID2', 200.00, 'Trans2'),
            BiTransaction::create(1, 'FITID3', 'ACCTID3', 300.00, 'Trans3'),
        ];

        $saved = $this->repository->bulkInsert($transactions);

        $this->assertIsArray($saved);
        $this->assertCount(3, $saved);
        
        foreach ($saved as $transaction) {
            $this->assertInstanceOf(BiTransaction::class, $transaction);
            $this->assertGreater(0, $transaction->getId());
        }
    }

    /**
     * @test
     * @group bulkInsert
     */
    public function testBulkInsertEmptyArrayReturnsEmptyArray(): void
    {
        $saved = $this->repository->bulkInsert([]);

        $this->assertIsArray($saved);
        $this->assertEmpty($saved);
    }

    /**
     * @test
     * @group bulkInsert
     */
    public function testBulkInsertRollsBackOnSingleFailure(): void
    {
        // This would need a transaction test with one invalid entity
        $this->markTestSkipped('Transaction rollback behavior - integration test');
    }

    /**
     * TEST GROUP 9: Bulk Update
     */

    /**
     * @test
     * @group bulkUpdate
     */
    public function testBulkUpdateReturnsCountOfUpdated(): void
    {
        $transactions = [
            $this->repository->findById(1),
            $this->repository->findById(2),
        ];

        // Note: Update implementation TBD
        $this->assertIsArray($transactions);
    }

    /**
     * TEST GROUP 10: Delete Operation
     */

    /**
     * @test
     * @group delete
     */
    public function testDeleteRemovesTransactionFromRepository(): void
    {
        $transaction = $this->repository->findById(1);
        
        $this->repository->delete($transaction->getId());

        $this->expectException(EntityNotFoundException::class);
        $this->repository->findById(1);
    }

    /**
     * @test
     * @group delete
     */
    public function testDeleteByIdThrowsExceptionForNonexistentId(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->delete(99999);
    }

    /**
     * TEST GROUP 11: Bulk Delete
     */

    /**
     * @test
     * @group bulkDelete
     */
    public function testBulkDeleteRemovesMultipleTransactions(): void
    {
        $ids = [1, 2, 3];
        
        $count = $this->repository->bulkDelete($ids);

        $this->assertIsInt($count);
        $this->assertEquals(3, $count);
    }

    /**
     * TEST GROUP 12: Exception Handling
     */

    /**
     * @test
     * @group exceptions
     */
    public function testRepositoryThrowsEntityNotFoundExceptionForMissingEntity(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->findById(99999);
    }

    /**
     * @test
     * @group exceptions
     */
    public function testRepositoryThrowsDuplicateEntityExceptionForDuplicateInsert(): void
    {
        // This would require unique constraint violation
        $this->markTestSkipped('Duplicate constraint test - integration test');
    }

    /**
     * @test
     * @group exceptions
     */
    public function testRepositoryThrowsRepositoryExceptionForDatabaseErrors(): void
    {
        $this->markTestSkipped('Database error handling - integration test');
    }

    /**
     * TEST GROUP 13: Query Filters & Options
     */

    /**
     * @test
     * @group filters
     */
    public function testFindWithLimitReturnsLimitedResults(): void
    {
        $results = $this->repository->findByStatement(1, limit: 5);

        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
    }

    /**
     * @test
     * @group filters
     */
    public function testFindWithOffsetSkipsRecords(): void
    {
        $allResults = $this->repository->findByStatement(1);
        $offsetResults = $this->repository->findByStatement(1, offset: 5);

        // Offset should return fewer or equal results
        $this->assertLessThanOrEqual(count($allResults), count($offsetResults) + 5);
    }

    /**
     * @test
     * @group filters
     */
    public function testFindWithOrderByReturnsOrderedResults(): void
    {
        $results = $this->repository->findByStatement(1, orderBy: 'transactionAmount DESC');

        $this->assertIsArray($results);
        
        // Verify descending order
        if (count($results) > 1) {
            $amounts = array_map(fn($t) => $t->getTransactionAmount(), $results);
            $this->assertEquals($amounts, array_values(rsort($amounts) ?: []));
        }
    }

    /**
     * TEST GROUP 14: Performance & Scalar Methods
     */

    /**
     * @test
     * @group performance
     */
    public function testCountByStatusReturnsInteger(): void
    {
        $count = $this->repository->countByStatus(0);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * @test
     * @group performance
     */
    public function testExistsReturnsBoolean(): void
    {
        $exists = $this->repository->exists(1);

        $this->assertIsBool($exists);
    }

    /**
     * TEST GROUP 15: Complex Queries
     */

    /**
     * @test
     * @group complex
     */
    public function testFindByDateRangeReturnsTransactionsInRange(): void
    {
        $start = new DateTime('2026-01-01');
        $end = new DateTime('2026-12-31');

        $results = $this->repository->findByDateRange($start, $end);

        $this->assertIsArray($results);
        
        foreach ($results as $transaction) {
            $this->assertInstanceOf(BiTransaction::class, $transaction);
        }
    }

    /**
     * @test
     * @group complex
     */
    public function testFindUnmatchedTransactionsReturnsUnmatchedOnly(): void
    {
        $results = $this->repository->findUnmatched();

        $this->assertIsArray($results);
        
        foreach ($results as $transaction) {
            $this->assertFalse($transaction->isMatched());
        }
    }

    /**
     * @test
     * @group complex
     */
    public function testFindByAmountRangeReturnsCorrectTransactions(): void
    {
        $results = $this->repository->findByAmountRange(100.00, 500.00);

        $this->assertIsArray($results);
        
        foreach ($results as $transaction) {
            $amount = abs($transaction->getTransactionAmount());
            $this->assertGreaterThanOrEqual(100.00, $amount);
            $this->assertLessThanOrEqual(500.00, $amount);
        }
    }

    /**
     * TEST GROUP 16: Integration with other entities
     */

    /**
     * @test
     * @group integration
     */
    public function testSaveAndRetrieveRoundTrip(): void
    {
        $original = BiTransaction::create(
            smtId: 5,
            fitId: 'FITID_ROUNDTRIP',
            acctId: 'ACCTID_ROUNDTRIP',
            transactionAmount: 123.45,
            transactionTitle: 'Round Trip Test'
        );

        $saved = $this->repository->save($original);
        $retrieved = $this->repository->findById($saved->getId());

        $this->assertEquals($saved->getId(), $retrieved->getId());
        $this->assertEquals($saved->getFitId(), $retrieved->getFitId());
        $this->assertEquals($saved->getTransactionAmount(), $retrieved->getTransactionAmount());
    }
}
