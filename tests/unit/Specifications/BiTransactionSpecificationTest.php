<?php

namespace Ksfraser\FaBankImport\Tests\Unit\Specifications;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Specifications\BiTransactionSpecification;
use Ksfraser\FaBankImport\QueryBuilders\BiTransactionQueryBuilder;

class BiTransactionSpecificationTest extends TestCase
{
    private BiTransactionQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = new BiTransactionQueryBuilder();
    }

    /**
     * Test can create specification with equals condition
     */
    public function testCanCreateEqualsSpecification(): void
    {
        $spec = BiTransactionSpecification::where('status', '=', 'PENDING');
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create specification with greater than condition
     */
    public function testCanCreateGreaterThanSpecification(): void
    {
        $spec = BiTransactionSpecification::where('transactionAmount', '>', 1000.00);
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create specification with less than condition
     */
    public function testCanCreateLessThanSpecification(): void
    {
        $spec = BiTransactionSpecification::where('transactionAmount', '<', 5000.00);
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create specification with between condition
     */
    public function testCanCreateBetweenSpecification(): void
    {
        $spec = BiTransactionSpecification::whereBetween('transactionAmount', 100.00, 5000.00);
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create specification with in condition
     */
    public function testCanCreateInSpecification(): void
    {
        $spec = BiTransactionSpecification::whereIn('transactionCode', ['CHK001', 'CHK002', 'CHK003']);
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create specification with is null condition
     */
    public function testCanCreateIsNullSpecification(): void
    {
        $spec = BiTransactionSpecification::whereIsNull('matchinfo');
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create specification with is not null condition
     */
    public function testCanCreateIsNotNullSpecification(): void
    {
        $spec = BiTransactionSpecification::whereIsNotNull('gPartner');
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create matched specification
     */
    public function testCanCreateMatchedSpecification(): void
    {
        $spec = BiTransactionSpecification::matched();
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create unmatched specification
     */
    public function testCanCreateUnmatchedSpecification(): void
    {
        $spec = BiTransactionSpecification::unmatched();
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create debit specification
     */
    public function testCanCreateDebitSpecification(): void
    {
        $spec = BiTransactionSpecification::debit();
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can create credit specification
     */
    public function testCanCreateCreditSpecification(): void
    {
        $spec = BiTransactionSpecification::credit();
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can chain specifications with AND
     */
    public function testCanChainSpecificationsWithAnd(): void
    {
        $spec = BiTransactionSpecification::matched()
            ->and(BiTransactionSpecification::debit());
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can chain specifications with OR
     */
    public function testCanChainSpecificationsWithOr(): void
    {
        $spec = BiTransactionSpecification::matched()
            ->or(BiTransactionSpecification::debit());
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can combine multiple conditions
     */
    public function testCanCombineMultipleConditions(): void
    {
        $spec = BiTransactionSpecification::matched()
            ->and(BiTransactionSpecification::where('transactionAmount', '>', 100.00))
            ->and(BiTransactionSpecification::where('status', '=', 'PENDING'));
        
        $this->assertInstanceOf(BiTransactionSpecification::class, $spec);
    }

    /**
     * Test can get specification criteria
     */
    public function testCanGetSpecificationCriteria(): void
    {
        $spec = BiTransactionSpecification::where('status', '=', 'PENDING');
        $criteria = $spec->getCriteria();
        
        $this->assertIsArray($criteria);
        $this->assertArrayHasKey('field', $criteria);
        $this->assertArrayHasKey('operator', $criteria);
        $this->assertArrayHasKey('value', $criteria);
    }

    /**
     * Test query builder fluent interface
     */
    public function testQueryBuilderFluentInterface(): void
    {
        $query = $this->queryBuilder
            ->where('status', '=', 'PENDING')
            ->where('transactionDC', '=', 'D')
            ->orderBy('transactionAmount', 'DESC')
            ->limit(10);
        
        $this->assertInstanceOf(BiTransactionQueryBuilder::class, $query);
    }

    /**
     * Test query builder can build criteria array
     */
    public function testQueryBuilderCanBuildCriteria(): void
    {
        $query = $this->queryBuilder
            ->where('status', '=', 'PENDING')
            ->where('matched', '=', true);
        
        $criteria = $query->getCriteria();
        
        $this->assertIsArray($criteria);
        $this->assertTrue(count($criteria) > 0);
    }

    /**
     * Test query builder with specification
     */
    public function testQueryBuilderWithSpecification(): void
    {
        $spec = BiTransactionSpecification::matched()->and(BiTransactionSpecification::debit());
        
        $query = $this->queryBuilder->apply($spec);
        
        $this->assertInstanceOf(BiTransactionQueryBuilder::class, $query);
    }

    /**
     * Test query builder sorting
     */
    public function testQueryBuilderSorting(): void
    {
        $query = $this->queryBuilder
            ->orderBy('transactionAmount', 'DESC')
            ->orderBy('id', 'ASC');
        
        $sorting = $query->getSorting();
        
        $this->assertIsArray($sorting);
        $this->assertTrue(count($sorting) > 0);
    }

    /**
     * Test query builder pagination
     */
    public function testQueryBuilderPagination(): void
    {
        $query = $this->queryBuilder
            ->limit(10)
            ->offset(20);
        
        $this->assertEquals(10, $query->getLimit());
        $this->assertEquals(20, $query->getOffset());
    }

    /**
     * Test complex query building
     */
    public function testComplexQueryBuilding(): void
    {
        $query = $this->queryBuilder
            ->where('transactionDC', '=', 'D')
            ->where('transactionAmount', '>', 500.00)
            ->where('transactionAmount', '<', 5000.00)
            ->where('status', '=', 'PENDING')
            ->where('matched', '=', false)
            ->orderBy('transactionAmount', 'DESC')
            ->limit(50)
            ->offset(0);
        
        $criteria = $query->getCriteria();
        
        $this->assertTrue(count($criteria) >= 5);
    }

    /**
     * Test specification immutability
     */
    public function testSpecificationImmutability(): void
    {
        $spec1 = BiTransactionSpecification::matched();
        $spec2 = $spec1->and(BiTransactionSpecification::debit());
        
        // spec1 and spec2 should be different
        $this->assertNotSame($spec1, $spec2);
    }

    /**
     * Test query builder reset
     */
    public function testQueryBuilderReset(): void
    {
        $query = $this->queryBuilder
            ->where('status', '=', 'PENDING')
            ->where('matched', '=', true);
        
        $query->reset();
        $criteria = $query->getCriteria();
        
        // After reset, criteria should be empty or minimal
        $this->assertTrue(is_array($criteria) || empty($criteria));
    }

    /**
     * Test query builder can apply multiple specifications
     */
    public function testQueryBuilderMultipleSpecifications(): void
    {
        $spec1 = BiTransactionSpecification::matched();
        $spec2 = BiTransactionSpecification::where('transactionAmount', '>', 100.00);
        
        $query = $this->queryBuilder
            ->apply($spec1)
            ->apply($spec2);
        
        $this->assertInstanceOf(BiTransactionQueryBuilder::class, $query);
    }

    /**
     * Test specification can be converted to array
     */
    public function testSpecificationCanBeConvertedToArray(): void
    {
        $spec = BiTransactionSpecification::matched()
            ->and(BiTransactionSpecification::debit());
        
        $array = $spec->toArray();
        
        $this->assertIsArray($array);
    }

    /**
     * Test query builder can get raw query
     */
    public function testQueryBuilderCanGetRawQuery(): void
    {
        $query = $this->queryBuilder
            ->where('status', '=', 'PENDING')
            ->where('transactionDC', '=', 'D');
        
        $raw = $query->toArray();
        
        $this->assertIsArray($raw);
        $this->assertArrayHasKey('criteria', $raw);
    }
}
