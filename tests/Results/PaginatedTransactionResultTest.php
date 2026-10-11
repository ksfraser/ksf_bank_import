<?php

namespace Ksfraser\FaBankImport\Tests\Results;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Results\PaginatedTransactionResult;

/**
 * Unit tests for PaginatedTransactionResult Value Object
 * 
 * Tests type safety, validation, and helper methods
 * 
 * @covers \Ksfraser\FaBankImport\Results\PaginatedTransactionResult
 */
class PaginatedTransactionResultTest extends TestCase
{
	/**
	 * Test successful construction with valid values
	 */
	public function testConstructionWithValidValues(): void
	{
		$transactions = ['TX001' => [['id' => 1, 'amount' => 100]]];
		$result = new PaginatedTransactionResult(
			$transactions,
			50,
			1,
			10,
			0,
			5
		);

		$this->assertSame($transactions, $result->transactions);
		$this->assertSame(50, $result->total_count);
		$this->assertSame(1, $result->current_page);
		$this->assertSame(10, $result->total_pages);
		$this->assertSame(0, $result->offset);
		$this->assertSame(5, $result->limit);
	}

	/**
	 * Test constructor rejects negative total_count
	 */
	public function testConstructionRejectsNegativeTotalCount(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('total_count must be >= 0');

		new PaginatedTransactionResult([], -1, 1, 0, 0, 5);
	}

	/**
	 * Test constructor rejects current_page < 1
	 */
	public function testConstructionRejectsInvalidCurrentPage(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('current_page must be >= 1');

		new PaginatedTransactionResult([], 0, 0, 0, 0, 5);
	}

	/**
	 * Test constructor rejects negative total_pages
	 */
	public function testConstructionRejectsNegativeTotalPages(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('total_pages must be >= 0');

		new PaginatedTransactionResult([], 0, 1, -1, 0, 5);
	}

	/**
	 * Test constructor rejects negative offset
	 */
	public function testConstructionRejectsNegativeOffset(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('offset must be >= 0');

		new PaginatedTransactionResult([], 0, 1, 0, -1, 5);
	}

	/**
	 * Test constructor rejects invalid limit (< 1)
	 */
	public function testConstructionRejectsInvalidLimit(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('limit must be >= 1');

		new PaginatedTransactionResult([], 0, 1, 0, 0, 0);
	}

	/**
	 * Test constructor rejects current_page > total_pages
	 */
	public function testConstructionRejectsCurrentPageExceedsTotalPages(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('current_page (3) cannot exceed total_pages (2)');

		new PaginatedTransactionResult([], 100, 3, 2, 0, 5);
	}

	/**
	 * Test hasNextPage() returns true when more pages exist
	 */
	public function testHasNextPageReturnsTrue(): void
	{
		$result = new PaginatedTransactionResult([], 100, 1, 5, 0, 5);
		$this->assertTrue($result->hasNextPage());
	}

	/**
	 * Test hasNextPage() returns false on last page
	 */
	public function testHasNextPageReturnsFalseOnLastPage(): void
	{
		$result = new PaginatedTransactionResult([], 25, 5, 5, 20, 5);
		$this->assertFalse($result->hasNextPage());
	}

	/**
	 * Test hasPreviousPage() returns true after first page
	 */
	public function testHasPreviousPageReturnsTrue(): void
	{
		$result = new PaginatedTransactionResult([], 50, 3, 10, 10, 5);
		$this->assertTrue($result->hasPreviousPage());
	}

	/**
	 * Test hasPreviousPage() returns false on first page
	 */
	public function testHasPreviousPageReturnsFalseOnFirstPage(): void
	{
		$result = new PaginatedTransactionResult([], 100, 1, 10, 0, 5);
		$this->assertFalse($result->hasPreviousPage());
	}

	/**
	 * Test readonly properties cannot be modified
	 */
	public function testPropertiesAreReadonly(): void
	{
		$result = new PaginatedTransactionResult([], 50, 1, 10, 0, 5);
		
		$this->expectException(\Error::class);
		$result->total_count = 100; // Should fail - readonly
	}

	/**
	 * Test edge case: single page result
	 */
	public function testSinglePageResult(): void
	{
		$result = new PaginatedTransactionResult([], 5, 1, 1, 0, 5);
		
		$this->assertFalse($result->hasNextPage());
		$this->assertFalse($result->hasPreviousPage());
		$this->assertSame(1, $result->total_pages);
		$this->assertSame(5, $result->total_count);
	}

	/**
	 * Test edge case: empty result set (0 total_count, 0 pages)
	 */
	public function testEmptyResultSet(): void
	{
		$result = new PaginatedTransactionResult([], 0, 1, 0, 0, 5);
		
		$this->assertFalse($result->hasNextPage());
		$this->assertFalse($result->hasPreviousPage());
		$this->assertSame(0, $result->total_count);
		$this->assertSame(0, $result->total_pages);
	}

	/**
	 * Test middle page navigation
	 */
	public function testMiddlePageNavigation(): void
	{
		$result = new PaginatedTransactionResult([], 100, 5, 10, 20, 5);
		
		$this->assertTrue($result->hasNextPage());
		$this->assertTrue($result->hasPreviousPage());
		$this->assertSame(5, $result->current_page);
		$this->assertSame(10, $result->total_pages);
	}
}
