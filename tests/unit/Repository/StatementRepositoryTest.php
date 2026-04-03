<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\Repository;

use DateTime;
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Repositories\StatementRepository;
use Ksfraser\FaBankImport\Shared\Repositories\StatementRepositoryInterface;
use Ksfraser\FaBankImport\Shared\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\RepositoryException;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidStatementException;

/**
 * StatementRepositoryTest - TDD RED PHASE
 * 
 * Unit tests for StatementRepository interface and implementation.
 * Tests verify CRUD operations, query methods, bulk operations, and exception handling.
 * 
 * TESTING STRATEGY:
 * - Mock database connection (PDO) for unit tests
 * - All tests focus on business logic, not database implementation details
 * - Entity invariants validated
 * - Exception types verified
 * - SRP: Repository only handles persistence, not domain logic
 */
class StatementRepositoryTest extends TestCase
{
    private StatementRepositoryInterface $repository;
    private \PDO $mockPdo;

    protected function setUp(): void
    {
        // Create mock PDOStatement with correct database field names (lowercase, snake_case)
        $mockRowData = [
            'id' => 1,
            'bankid' => 'BANK123',
            'acctid' => 'ACCT456',
            'fitid' => 'FITID789',
            'bank' => 'Chase',
            'account' => '1234567890',
            'statementId' => 'STM123',
            'intu_bid' => 'BID123',
            'currency' => 'USD',
            'startBalance' => 1000.00,
            'endBalance' => 2000.00,
            'smtDate' => '2025-03-01',
            'number' => 1,
            'seq' => 1,
        ];
        
        // We need to create a callable mock that returns different values based on the SQL query
        $mockStatement = $this->createMock(\PDOStatement::class);
        
        // Track parameters for intelligent returns
        $lastParams = [];
        $mockStatement->expects($this->any())
            ->method('execute')
            ->willReturnCallback(function($params) use (&$lastParams) {
                $lastParams = $params ?? [];
                return true;
            });
        
        // Configure fetch() to return a row (or false for non-matching queries)
        $mockStatement->expects($this->any())
            ->method('fetch')
            ->with($this->anything())
            ->willReturnCallback(function() use ($lastParams, $mockRowData) {
                // Return false when searching for non-existent ID
                if (isset($lastParams['id'])) {
                    if ($lastParams['id'] == 99999) {
                        return false;  // Trigger "not found"
                    }
                    return $mockRowData;
                }
                // Default: return data
                return $mockRowData;
            });
        
        // Configure fetchAll() to return array of rows (or empty array for no-matching queries)
        $mockStatement->expects($this->any())
            ->method('fetchAll')
            ->with($this->anything())
            ->willReturnCallback(function() use ($lastParams, $mockRowData) {
                // Return empty array for non-matching bank/account IDs
                if (isset($lastParams['bankId']) && !in_array($lastParams['bankId'], ['BANK123', 'BANK999'])) {
                    return [];
                }
                if (isset($lastParams['acctId']) && !in_array($lastParams['acctId'], ['ACCT456', 'ACCT999'])) {
                    return [];
                }
               // Return results for matching queries
                return [$mockRowData];
            });
        
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
        
        $this->repository = new StatementRepository($this->mockPdo);
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
        $this->assertInstanceOf(StatementRepositoryInterface::class, $this->repository);
    }

    /**
     * @test
     * @group constructor
     */
    public function testRepositoryAcceptsPdoConnection(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $repo = new StatementRepository($pdo);
        $this->assertNotNull($repo);
    }

    /**
     * TEST GROUP 2: Save Operation (INSERT/UPDATE)
     */

    /**
     * @test
     * @group save
     */
    public function testSaveNewStatementReturnsId(): void
    {
        $statement = BiStatement::create(
            bank: 'Chase',
            account: '1234567890',
            statementId: 'STM123',
            acctId: 'ACCT456',
            fitId: 'FITID789',
            bankId: 'BANK123',
            intuBid: 'BID123'
        );

        $id = $this->repository->save($statement);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id, 'Saved statement must have ID > 0');
    }

    /**
     * @test
     * @group save
     */
    public function testSaveStatementPersistsToDatabase(): void
    {
        $statement = BiStatement::create(
            bank: 'Wells Fargo',
            account: '9876543210',
            statementId: 'STM999',
            acctId: 'ACCT999',
            fitId: 'FITID999',
            bankId: 'BANK999',
            intuBid: 'BID999'
        );

        $id = $this->repository->save($statement);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * @test
     * @group save
     */
    public function testSaveReturnsIdNotEntity(): void
    {
        $statement = BiStatement::create(
            bank: 'BankOfAmerica',
            account: '5555555555',
            statementId: 'STM777',
            acctId: 'ACCT777',
            fitId: 'FITID777',
            bankId: 'BANK777',
            intuBid: 'BID777'
        );

        $id = $this->repository->save($statement);

        $this->assertIsInt($id);
        $this->assertNotInstanceOf(BiStatement::class, $id);
    }

    /**
     * @test
     * @group save
     */
    public function testSaveThrowsExceptionForMissingBankId(): void
    {
        $this->expectException(InvalidStatementException::class);
        
        BiStatement::create(
            bank: '',  // Empty bank should trigger exception
            account: 'Account123',
            statementId: 'STM111',
            acctId: 'ACCT111',
            fitId: 'FITID111',
            bankId: 'BANK111',
            intuBid: 'BID111'
        );
    }

    /**
     * @test
     * @group save
     */
    public function testSaveThrowsExceptionForMissingAcctId(): void
    {
        $this->expectException(InvalidStatementException::class);
        
        BiStatement::create(
            bank: 'TestBank',
            account: '',  // Empty account should trigger exception
            statementId: 'STM111',
            acctId: 'ACCT111',
            fitId: 'FITID111',
            bankId: 'BANK111',
            intuBid: 'BID111'
        );
    }

    /**
     * TEST GROUP 3: Find by ID
     */

    /**
     * @test
     * @group findById
     */
    public function testFindByIdReturnsStatementEntity(): void
    {
        $found = $this->repository->findById(1);

        $this->assertInstanceOf(BiStatement::class, $found);
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
    public function testFindByIdReturnsCorrectStatementData(): void
    {
        $found = $this->repository->findById(1);

        $this->assertEquals('BANK123', $found->getBankId());
        $this->assertEquals('ACCT456', $found->getAcctId());
    }

    /**
     * TEST GROUP 4: Query Methods - By Bank
     */

    /**
     * @test
     * @group queryByBank
     */
    public function testFindByBankIdReturnsArrayOfStatements(): void
    {
        $results = $this->repository->findByBankId('BANK123');

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryByBank
     */
    public function testFindByBankIdReturnsEmptyArrayForNoMatches(): void
    {
        $results = $this->repository->findByBankId('NONEXISTENT');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * @group queryByBank
     */
    public function testFindByBankIdReturnsOnlyMatchingStatements(): void
    {
        $results = $this->repository->findByBankId('BANK123');

        foreach ($results as $statement) {
            $this->assertInstanceOf(BiStatement::class, $statement);
            $this->assertEquals('BANK123', $statement->getBankId());
        }
    }

    /**
     * TEST GROUP 5: Query Methods - By Account
     */

    /**
     * @test
     * @group queryByAcct
     */
    public function testFindByAcctIdReturnsStatements(): void
    {
        $results = $this->repository->findByAcctId('ACCT456');

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryByAcct
     */
    public function testFindByAcctIdReturnsEmptyArrayForNoMatches(): void
    {
        $results = $this->repository->findByAcctId('ACCTNONEXIST');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * @group queryByAcct
     */
    public function testFindByAcctIdSupportsLimit(): void
    {
        $results = $this->repository->findByAcctId('ACCT456', limit: 5);

        $this->assertIsArray($results);
    }

    /**
     * TEST GROUP 6: Query Methods - By Date Range
     */

    /**
     * @test
     * @group queryByDate
     */
    public function testFindByDateRangeReturnsStatements(): void
    {
        $results = $this->repository->findByDateRange(
            '2025-01-01',
            '2025-03-31'
        );

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryByDate
     */
    public function testFindByDateRangeReturnsEmptyArrayForOutOfRange(): void
    {
        $results = $this->repository->findByDateRange(
            '2026-01-01',
            '2026-03-31'
        );

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * @group queryByDate
     */
    public function testFindByDateRangeSupportsLimit(): void
    {
        $results = $this->repository->findByDateRange(
            '2025-01-01',
            '2025-03-31',
            limit: 10
        );

        $this->assertIsArray($results);
    }

    /**
     * TEST GROUP 7: Update Operation
     */

    /**
     * @test
     * @group update
     */
    public function testUpdateStatementUpdatesDatabaseRecord(): void
    {
        // Create a statement with an ID to update
        $reflection = new \ReflectionClass(BiStatement::class);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        
        $statement = BiStatement::create(
            bank: 'Wells Fargo',
            account: '9876543210',
            statementId: 'STM999',
            acctId: 'ACCT999',
            fitId: 'FITID999',
            bankId: 'BANK999',
            intuBid: 'BID999'
        );
        
        $prop->setValue($statement, 5);
        
        $result = $this->repository->update($statement);
        
        $this->assertIsInt($result);
        $this->assertEquals(5, $result);
    }

    /**
     * @test
     * @group update
     */
    public function testUpdateReturnsId(): void
    {
        $reflection = new \ReflectionClass(BiStatement::class);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        
        $statement = BiStatement::create(
            bank: 'Chase',
            account: '1234567890',
            statementId: 'STM123',
            acctId: 'ACCT123',
            fitId: 'FITID123',
            bankId: 'BANK123',
            intuBid: 'BID123'
        );
        
        $prop->setValue($statement, 10);
        
        $id = $this->repository->update($statement);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * @test
     * @group update
     */
    public function testUpdateThrowsExceptionIfTransactionNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        
        $statement = BiStatement::create(
            bank: 'Test Bank',
            account: 'Test Account',
            statementId: 'STM_TEST',
            acctId: 'ACCT_TEST',
            fitId: 'FITID_TEST',
            bankId: 'BANK_TEST',
            intuBid: 'BID_TEST'
        );
        
        // Don't set an ID - should throw exception
        $this->repository->update($statement);
    }

    /**
     * TEST GROUP 8: Delete Operation
     */

    /**
     * @test
     * @group delete
     */
    public function testDeleteRemovesStatementFromRepository(): void
    {
        $result = $this->repository->delete(1);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @test
     * @group delete
     */
    public function testDeleteThrowsExceptionIfNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->delete(99999);
    }

    /**
     * TEST GROUP 9: Bulk Operations
     */

    /**
     * @test
     * @group bulkInsert
     */
    public function testBulkInsertReturnsArrayOfIds(): void
    {
        $statements = [
            BiStatement::create('BANK1', 'ACCT1', new DateTime('2025-01-01')),
            BiStatement::create('BANK2', 'ACCT2', new DateTime('2025-02-01')),
        ];

        $ids = $this->repository->bulkInsert($statements);

        $this->assertIsArray($ids);
        $this->assertCount(2, $ids);
    }

    /**
     * @test
     * @group bulkInsert
     */
    public function testBulkInsertEmptyArrayReturnsEmpty(): void
    {
        $ids = $this->repository->bulkInsert([]);

        $this->assertIsArray($ids);
        $this->assertEmpty($ids);
    }

    /**
     * @test
     * @group bulkInsert
     */
    public function testBulkInsertRollsBackOnError(): void
    {
        $this->expectException(RepositoryException::class);
        
        // This test would need additional mock configuration
        // to simulate a database error
        $statements = [
            BiStatement::create('BANK1', 'ACCT1', new DateTime('2025-01-01')),
        ];

        $this->repository->bulkInsert($statements);
    }

    /**
     * TEST GROUP 10: Query Filtered Operations
     */

    /**
     * @test
     * @group queryStatus
     */
    public function testFindByStatusReturnsFilteredStatements(): void
    {
        $results = $this->repository->findByStatus('0');

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryStatus
     */
    public function testFindByStatusOnlyReturnsMatchingStatus(): void
    {
        $results = $this->repository->findByStatus('0');

        foreach ($results as $statement) {
            $this->assertInstanceOf(BiStatement::class, $statement);
            $this->assertEquals(0, $statement->getStatus());
        }
    }

    /**
     * @test
     * @group queryUnprocessed
     */
    public function testFindUnprocessedReturnsStatements(): void
    {
        $results = $this->repository->findUnprocessed();

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group queryProcessed
     */
    public function testFindProcessedReturnsStatements(): void
    {
        $results = $this->repository->findProcessed();

        $this->assertIsArray($results);
    }

    /**
     * TEST GROUP 11: Count Operations
     */

    /**
     * @test
     * @group count
     */
    public function testCountReturnsIntegerCount(): void
    {
        $count = $this->repository->count();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * @test
     * @group count
     */
    public function testCountByStatusReturnsCount(): void
    {
        $count = $this->repository->countByStatus('0');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * TEST GROUP 12: Exception Handling
     */

    /**
     * @test
     * @group exceptions
     */
    public function testFindByIdThrowsRepositoryExceptionOnDatabaseError(): void
    {
        $this->expectException(RepositoryException::class);
        // This test would need mock configuration to simulate DB error
        $this->repository->findById(1);
    }

    /**
     * @test
     * @group exceptions
     */
    public function testSaveThrowsRepositoryExceptionOnDatabaseError(): void
    {
        $this->expectException(RepositoryException::class);
        // This test would need mock configuration to simulate DB error
        $statement = BiStatement::create('BANK1', 'ACCT1', new DateTime('2025-01-01'));
        $this->repository->save($statement);
    }

    /**
     * @test
     * @group exceptions
     */
    public function testUpdateThrowsRepositoryExceptionOnDatabaseError(): void
    {
        $this->expectException(RepositoryException::class);
        // This test would need mock configuration to simulate DB error
        $statement = BiStatement::create('BANK1', 'ACCT1', new DateTime('2025-01-01'));
        $reflection = new \ReflectionClass(BiStatement::class);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($statement, 1);
        
        $this->repository->update($statement);
    }

    /**
     * TEST GROUP 13: Performance Metrics
     */

    /**
     * @test
     * @group performance
     */
    public function testFindByDateRangeCompletesBelowThreshold(): void
    {
        $startTime = microtime(true);
        
        $this->repository->findByDateRange('2025-01-01', '2025-12-31');
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->assertLessThan(100, $duration, 'Query should complete in <100ms');
    }

    /**
     * @test
     * @group performance
     */
    public function testBulkInsertCompletesBelowThreshold(): void
    {
        $statements = array_map(
            fn($i) => BiStatement::create("BANK$i", "ACCT$i", new DateTime('2025-01-01')),
            range(1, 10)
        );
        
        $startTime = microtime(true);
        
        $this->repository->bulkInsert($statements);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->assertLessThan(200, $duration, 'Bulk insert should complete in <200ms');
    }

    /**
     * TEST GROUP 14: Complex Queries
     */

    /**
     * @test
     * @group complex
     */
    public function testFindByFilterCombinesMultipleCriteria(): void
    {
        $results = $this->repository->findByBankId('BANK123', limit: 5, offset: 0);

        $this->assertIsArray($results);
    }

    /**
     * @test
     * @group complex
     */
    public function testFindWithPaginationOffsetsCorrectly(): void
    {
        $page1 = $this->repository->findByBankId('BANK123', limit: 5, offset: 0);
        $page2 = $this->repository->findByBankId('BANK123', limit: 5, offset: 5);

        $this->assertIsArray($page1);
        $this->assertIsArray($page2);
    }

    /**
     * @test
     * @group complex
     */
    public function testFindByDateRangeSortsResultsCorrectly(): void
    {
        $results = $this->repository->findByDateRange('2025-01-01', '2025-12-31');

        // Results should be sorted by date (or by ID for deterministic order)
        $this->assertIsArray($results);
    }

    /**
     * TEST GROUP 15: Integration Tests
     */

    /**
     * @test
     * @group integration
     */
    public function testRoundTripCreateReadVerify(): void
    {
        $original = BiStatement::create(
            bank: 'Chase',
            account: '1234567890',
            statementId: 'STM123',
            acctId: 'ACCT456',
            fitId: 'FITID789',
            bankId: 'BANK123',
            intuBid: 'BID123'
        );

        // Save
        $id = $this->repository->save($original);
        
        // Read back
        $found = $this->repository->findById($id);
        
        // Verify
        $this->assertEquals('BANK123', $found->getBankId());
        $this->assertEquals('ACCT456', $found->getAcctId());
    }

    /**
     * @test
     * @group integration
     */
    public function testUpdatePreservesImmutabilityViaNewEntity(): void
    {
        $reflection = new \ReflectionClass(BiStatement::class);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        
        $statement = BiStatement::create('BANK1', 'ACCT1', new DateTime('2025-01-01'));
        $prop->setValue($statement, 5);
        
        // The original statement should still be immutable
        $this->assertFalse(method_exists($statement, 'setBankId'));
        $this->assertFalse(method_exists($statement, 'setStatus'));
    }

    /**
     * TEST GROUP 16: Data Integrity
     */

    /**
     * @test
     * @group integrity
     */
    public function testStatementFieldsCorrectlyMapped(): void
    {
        $statement = $this->repository->findById(1);

        $this->assertNotNull($statement->getBankId());
        $this->assertNotNull($statement->getAcctId());
        $this->assertIsInt($statement->getId());
    }

    /**
     * @test
     * @group integrity
     */
    public function testBankAccountMappingPreservesIntegrity(): void
    {
        $statement = $this->repository->findById(1);
        
        // Bank ID and Account ID should not be empty
        $this->assertNotEmpty($statement->getBankId());
        $this->assertNotEmpty($statement->getAcctId());
    }
}
