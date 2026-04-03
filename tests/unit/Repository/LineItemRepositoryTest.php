<?php

namespace Ksfraser\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BiLineItem;
use Ksfraser\FaBankImport\Shared\Repositories\LineItemRepository;
use Ksfraser\FaBankImport\Shared\Repositories\LineItemRepositoryInterface;
use Ksfraser\FaBankImport\Shared\Repositories\PrefixedRepositoryInterface;
use Ksfraser\FaBankImport\Shared\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Shared\Exceptions\RepositoryException;

/**
 * LineItemRepositoryTest - TDD PHASE
 * 
 * Unit tests for LineItemRepository interface and implementation.
 * Tests verify CRUD operations, query methods, and exception handling.
 * 
 * TESTING STRATEGY:
 * - Mock database connection (PDO) for unit tests
 * - All tests focus on business logic, not database implementation details
 * - Entity invariants validated
 * - Exception types verified
 * - SRP: Repository only handles persistence, not domain logic
 */
class LineItemRepositoryTest extends TestCase
{
    private LineItemRepositoryInterface $repository;
    private \PDO $mockPdo;
    private \PDOStatement $mockStatement;

    protected function setUp(): void
    {
        // Default mock row data for single item queries
        $defaultRowData = [
            'id' => 1,
            'bi_transaction_id' => 1,
            'amount' => 50.00,
            'fa_gl_account' => 1001,
            'fa_memo' => 'Test Line Item',
            'status' => 0,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
        ];
        
        $this->mockStatement = $this->createMock(\PDOStatement::class);
        
        // Configure execute() to always succeed
        $this->mockStatement->expects($this->any())
            ->method('execute')
            ->willReturn(true);
        
        // Configure fetch() to return a single row by default
        $this->mockStatement->expects($this->any())
            ->method('fetch')
            ->with($this->anything())
            ->willReturn($defaultRowData);
        
        // Configure fetchAll() to return array of rows by default
        $this->mockStatement->expects($this->any())
            ->method('fetchAll')
            ->with($this->anything())
            ->willReturn([$defaultRowData]);
        
        // Configure rowCount()
        $this->mockStatement->expects($this->any())
            ->method('rowCount')
            ->willReturn(1);
        
        // Create mock PDO
        $this->mockPdo = $this->createMock(\PDO::class);
        
        // Configure prepare() - uses same mock statement for all prepared statements
        $this->mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($this->mockStatement);
        
        // Configure query() for non-parameterized queries (aggregate functions)
        $this->mockPdo->expects($this->any())
            ->method('query')
            ->willReturn($this->mockStatement);
        
        // Configure lastInsertId() to return an ID
        $this->mockPdo->expects($this->any())
            ->method('lastInsertId')
            ->willReturn('1');
            
        // Configure setAttribute() for PDO attribute setting
        $this->mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $this->repository = new LineItemRepository($this->mockPdo);
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
        $this->assertInstanceOf(LineItemRepositoryInterface::class, $this->repository);
    }

    /**
     * @test
     * @group constructor
     */
    public function testRepositoryImplementsPrefixedInterface(): void
    {
        $this->assertInstanceOf(PrefixedRepositoryInterface::class, $this->repository);
    }

    /**
     * @test
     * @group constructor
     */
    public function testConstructorSetsDefaultPrefix(): void
    {
        $this->assertEquals('0_', $this->repository->getTablePrefix());
    }

    /**
     * @test
     * @group constructor
     */
    public function testConstructorAcceptsCustomPrefix(): void
    {
        $repo = new LineItemRepository($this->mockPdo, 'test_');
        $this->assertEquals('test_', $repo->getTablePrefix());
    }

    /**
     * TEST GROUP 2: Table Prefix Management (PrefixedRepositoryInterface)
     */

    /**
     * @test
     * @group prefix
     */
    public function testSetTablePrefix(): void
    {
        $this->repository->setTablePrefix('custom_');
        $this->assertEquals('custom_', $this->repository->getTablePrefix());
    }

    /**
     * @test
     * @group prefix
     */
    public function testGetTableName(): void
    {
        $this->assertEquals('bi_line_items', $this->repository->getTableName());
    }

    /**
     * @test
     * @group prefix
     */
    public function testGetFullTableName(): void
    {
        $this->assertEquals('0_bi_line_items', $this->repository->getFullTableName());
    }

    /**
     * @test
     * @group prefix
     */
    public function testGetFullTableNameWithCustomPrefix(): void
    {
        $this->repository->setTablePrefix('test_');
        $this->assertEquals('test_bi_line_items', $this->repository->getFullTableName());
    }

    /**
     * TEST GROUP 3: CRUD - Create (Save)
     */

    /**
     * @test
     * @group crud-create
     */
    public function testSaveNewLineItem(): void
    {
        // Create a new line item
        $lineItem = BiLineItem::create(1, 100.50);
        
        $this->repository->save($lineItem);
        
        // Test passes if no exception thrown
        $this->assertTrue(true);
    }

    /**
     * @test
     * @group crud-create
     */
    public function testSaveCallsInsertForNewItem(): void
    {
        $lineItem = BiLineItem::create(1, 50.25);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO'))
            ->willReturn($this->mockStatement);
        
        $this->repository->save($lineItem);
    }

    /**
     * TEST GROUP 4: CRUD - Read (Find)
     */

    /**
     * @test
     * @group crud-read
     */
    public function testFindByIdReturnsLineItem(): void
    {
        $lineItem = $this->repository->findById(1);
        
        $this->assertInstanceOf(BiLineItem::class, $lineItem);
        $this->assertEquals(1, $lineItem->getId());
    }

    /**
     * @test
     * @group crud-read
     */
    public function testFindByIdThrowsExceptionWhenNotFound(): void
    {
        // Create a custom mock for this test
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->any())
            ->method('execute')
            ->willReturn(true);
        $mockStmt->expects($this->any())
            ->method('fetch')
            ->willReturn(false);  // Simulate not found
        
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStmt);
        $mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $repo = new LineItemRepository($mockPdo);
        
        $this->expectException(EntityNotFoundException::class);
        $repo->findById(999);
    }

    /**
     * @test
     * @group crud-read
     */
    public function testFindByTransactionIdReturnsArray(): void
    {
        $lineItems = $this->repository->findByTransactionId(1);
        
        $this->assertIsArray($lineItems);
        $this->assertCount(1, $lineItems);
        $this->assertInstanceOf(BiLineItem::class, $lineItems[0]);
    }

    /**
     * @test
     * @group crud-read
     */
    public function testFindByTransactionIdReturnsEmptyArrayWhenNoneFound(): void
    {
        // Create a custom mock for this test
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->any())
            ->method('execute')
            ->willReturn(true);
        $mockStmt->expects($this->any())
            ->method('fetchAll')
            ->willReturn([]);  // Simulate empty result
        
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStmt);
        $mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $repo = new LineItemRepository($mockPdo);
        
        $lineItems = $repo->findByTransactionId(999);
        
        $this->assertIsArray($lineItems);
        $this->assertCount(0, $lineItems);
    }

    /**
     * @test
     * @group crud-read
     */
    public function testFindByGLAccountReturnsArray(): void
    {
        $lineItems = $this->repository->findByGLAccount(1001);
        
        $this->assertIsArray($lineItems);
        $this->assertCount(1, $lineItems);
        $this->assertInstanceOf(BiLineItem::class, $lineItems[0]);
    }

    /**
     * @test
     * @group crud-read
     */
    public function testFindByGLAccountReturnsEmptyArrayWhenNoneFound(): void
    {
        // Create a custom mock for this test
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->any())
            ->method('execute')
            ->willReturn(true);
        $mockStmt->expects($this->any())
            ->method('fetchAll')
            ->willReturn([]);  // Simulate empty result
        
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStmt);
        $mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $repo = new LineItemRepository($mockPdo);
        
        $lineItems = $repo->findByGLAccount(9999);
        
        $this->assertIsArray($lineItems);
        $this->assertCount(0, $lineItems);
    }

    /**
     * TEST GROUP 5: CRUD - Delete
     */

    /**
     * @test
     * @group crud-delete
     */
    public function testDeleteSucceeds(): void
    {
        // Test passes if no exception thrown
        $this->repository->delete(1);
        $this->assertTrue(true);
    }

    /**
     * TEST GROUP 6: Aggregate Operations
     */

    /**
     * @test
     * @group aggregate
     */
    public function testCountReturnsTotal(): void
    {
        // Create a custom mock for aggregate query
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->any())
            ->method('fetch')
            ->willReturn(['total' => 10]);
        
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->expects($this->any())
            ->method('query')
            ->willReturn($mockStmt);
        $mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $repo = new LineItemRepository($mockPdo);
        
        $count = $repo->count();
        
        $this->assertEquals(10, $count);
    }

    /**
     * @test
     * @group aggregate
     */
    public function testCountReturnsZeroWhenEmpty(): void
    {
        $this->mockStatement->expects($this->any())
            ->method('fetch')
            ->willReturn(['total' => 0]);
        
        $count = $this->repository->count();
        
        $this->assertEquals(0, $count);
    }

    /**
     * @test
     * @group aggregate
     */
    public function testGetTotalAmountReturnsSum(): void
    {
        // Create a custom mock for aggregate query
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->any())
            ->method('fetch')
            ->willReturn(['total' => 1000.50]);
        
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->expects($this->any())
            ->method('query')
            ->willReturn($mockStmt);
        $mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $repo = new LineItemRepository($mockPdo);
        
        $total = $repo->getTotalAmount();
        
        $this->assertEquals(1000.50, $total);
    }

    /**
     * @test
     * @group aggregate
     */
    public function testGetTotalAmountForTransactionReturnsSum(): void
    {
        // Create a custom mock for aggregate query
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->any())
            ->method('execute')
            ->willReturn(true);
        $mockStmt->expects($this->any())
            ->method('fetch')
            ->willReturn(['total' => 250.25]);
        
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStmt);
        $mockPdo->expects($this->any())
            ->method('setAttribute')
            ->willReturn(true);
        
        $repo = new LineItemRepository($mockPdo);
        
        $total = $repo->getTotalAmountForTransaction(1);
        
        $this->assertEquals(250.25, $total);
    }

    /**
     * TEST GROUP 7: Exception Handling
     */

    /**
     * @test
     * @group exceptions
     */
    public function testFindByIdThrowsRepositoryExceptionOnDatabaseError(): void
    {
        $this->mockStatement->expects($this->any())
            ->method('execute')
            ->willThrowException(new \PDOException('Database error'));
        
        $this->expectException(RepositoryException::class);
        $this->repository->findById(1);
    }

    /**
     * @test
     * @group exceptions
     */
    public function testFindByTransactionIdThrowsRepositoryExceptionOnDatabaseError(): void
    {
        $this->mockStatement->expects($this->any())
            ->method('execute')
            ->willThrowException(new \PDOException('Database error'));
        
        $this->expectException(RepositoryException::class);
        $this->repository->findByTransactionId(1);
    }

    /**
     * @test
     * @group exceptions
     */
    public function testDeleteThrowsRepositoryExceptionOnDatabaseError(): void
    {
        $this->mockStatement->expects($this->any())
            ->method('execute')
            ->willThrowException(new \PDOException('Database error'));
        
        $this->expectException(RepositoryException::class);
        $this->repository->delete(1);
    }

    /**
     * TEST GROUP 8: LineItem Entity Mapping
     */

    /**
     * @test
     * @group entity-mapping
     */
    public function testFindByIdMapsAllProperties(): void
    {
        $lineItem = $this->repository->findById(1);
        
        $this->assertEquals(1, $lineItem->getId());
        $this->assertEquals(1, $lineItem->getBiTransactionId());
        $this->assertEquals(50.00, $lineItem->getAmount());
        $this->assertEquals(1001, $lineItem->getFAGlAccount());
        $this->assertEquals('Test Line Item', $lineItem->getFAMemo());
        $this->assertEquals(0, $lineItem->getStatus());
        $this->assertEquals(0, $lineItem->getFATransType());
        $this->assertEquals(0, $lineItem->getFATransNo());
    }

    /**
     * @test
     * @group entity-mapping
     */
    public function testFindByTransactionIdMapsAll(): void
    {
        $lineItems = $this->repository->findByTransactionId(1);
        
        $this->assertGreaterThan(0, count($lineItems));
        
        foreach ($lineItems as $lineItem) {
            $this->assertInstanceOf(BiLineItem::class, $lineItem);
            $this->assertEquals(1, $lineItem->getBiTransactionId());
        }
    }
}
