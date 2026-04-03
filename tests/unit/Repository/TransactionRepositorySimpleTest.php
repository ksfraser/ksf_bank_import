<?php
namespace Ksfraser\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Shared\Repositories\TransactionRepository;

/**
 * Simple test to debug mock configuration
 */
class TransactionRepositorySimpleTest extends TestCase
{
    /**
     * @test
     */
    public function testMockPdoPrepareReturnsStatement(): void
    {
        // Create mock statement
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'FITID123',  // Note: lowercase fitid (database field name)
            'acctid' => 'ACCTID456',  // Note: lowercase acctid (database field name)
            'transactionAmount' => 100.50,
            'transactionTitle' => 'Test',
            'status' => 0,
        ]);
        
        // Create mock PDO
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStatement);
        $mockPdo->method('setAttribute')->willReturn(true);
        
        // Try to use it
        $repo = new TransactionRepository($mockPdo);
        $result = $repo->findById(1);
        
        $this->assertNotNull($result);
        $this->assertInstanceOf(BiTransaction::class, $result);
        $this->assertEquals(1, $result->getId());
    }
}
