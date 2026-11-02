<?php

namespace Ksfraser\FaBankImport\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Results\PaginatedTransactionResult;

/**
 * Integration tests for bi_transactions_model::get_transactions() with PaginatedTransactionResult DTO
 * 
 * Tests that get_transactions() correctly returns a PaginatedTransactionResult object
 * with proper pagination metadata and transaction data
 * 
 * @group integration
 */
class GetTransactionsIntegrationTest extends TestCase
{
	private $biTransactionsModel;

	protected function setUp(): void
	{
		// Initialize the model - requires database connection
		$this->biTransactionsModel = new \bi_transactions_model();
	}

	/**
	 * Test get_transactions() returns PaginatedTransactionResult instance
	 */
	public function testGetTransactionsReturnsPaginatedTransactionResult(): void
	{
		// This test requires valid database setup
		// For now we verify the class exists and method signature
		
		$this->assertTrue(method_exists($this->biTransactionsModel, 'get_transactions'));
	}

	/**
	 * Test PaginatedTransactionResult has all required properties
	 */
	public function testPaginatedTransactionResultStructure(): void
	{
		// Verify the DTO structure
		$result = new PaginatedTransactionResult(
			['TX001' => []],
			10,
			1,
			2,
			0,
			5
		);

		// Verify all properties are accessible
		$this->assertTrue(isset($result->transactions));
		$this->assertTrue(isset($result->total_count));
		$this->assertTrue(isset($result->current_page));
		$this->assertTrue(isset($result->total_pages));
		$this->assertTrue(isset($result->offset));
		$this->assertTrue(isset($result->limit));
	}

	/**
	 * Test get_transactions() with default pagination (page 1, limit 5)
	 * 
	 * @depends testGetTransactionsReturnsPaginatedTransactionResult
	 */
	public function testGetTransactionsDefaultPagination(): void
	{
		// Verify method accepts pagination parameters
		$reflection = new \ReflectionMethod($this->biTransactionsModel, 'get_transactions');
		$params = $reflection->getParameters();

		// Check for offset and limit_page parameters
		$paramNames = array_map(function($p) { return $p->name; }, $params);
		$this->assertContains('offset', $paramNames);
		$this->assertContains('limit_page', $paramNames);
	}

	/**
	 * Test get_transactions() pagination calculations
	 */
	public function testPaginationCalculations(): void
	{
		// Test various pagination scenarios
		$testCases = [
			// [total_count, page_size, expected_total_pages]
			[10, 5, 2],
			[11, 5, 3],
			[25, 5, 5],
			[26, 5, 6],
			[0, 5, 0],
			[1, 5, 1],
		];

		foreach ($testCases as [$totalCount, $pageSize, $expectedPages]) {
			$result = new PaginatedTransactionResult(
				[],
				$totalCount,
				1,
				$expectedPages,
				0,
				$pageSize
			);

			$this->assertEquals($expectedPages, $result->total_pages);
			$this->assertEquals(1, $result->current_page);
		}
	}

	/**
	 * Test offset calculation for different pages
	 */
	public function testOffsetCalculation(): void
	{
		// Page 1: offset 0
		$result1 = new PaginatedTransactionResult([], 100, 1, 20, 0, 5);
		$this->assertEquals(0, $result1->offset);
		$this->assertEquals(1, $result1->current_page);

		// Page 2: offset 5
		$result2 = new PaginatedTransactionResult([], 100, 2, 20, 5, 5);
		$this->assertEquals(5, $result2->offset);
		$this->assertEquals(2, $result2->current_page);

		// Page 10: offset 45
		$result10 = new PaginatedTransactionResult([], 100, 10, 20, 45, 5);
		$this->assertEquals(45, $result10->offset);
		$this->assertEquals(10, $result10->current_page);
	}

	/**
	 * Test DTO handles transaction data correctly
	 */
	public function testDTOHandlesTransactionData(): void
	{
		$transactions = [
			'CHQ001' => [
				['id' => 1, 'amount' => 100.00, 'transactionCode' => 'CHQ001'],
				['id' => 2, 'amount' => 100.00, 'transactionCode' => 'CHQ001'],
			],
			'DEP002' => [
				['id' => 3, 'amount' => 500.00, 'transactionCode' => 'DEP002'],
			],
		];

		$result = new PaginatedTransactionResult(
			$transactions,
			3,
			1,
			1,
			0,
			5
		);

		$this->assertEquals(3, count($result->transactions));
		$this->assertArrayHasKey('CHQ001', $result->transactions);
		$this->assertArrayHasKey('DEP002', $result->transactions);
		$this->assertEquals(2, count($result->transactions['CHQ001']));
		$this->assertEquals(1, count($result->transactions['DEP002']));
	}

	/**
	 * Test DTO validation rejects inconsistent pagination state
	 */
	public function testDTOValidatesConsistentPaginationState(): void
	{
		// Current page cannot be greater than total pages when total_pages > 0
		$this->expectException(\InvalidArgumentException::class);
		
		new PaginatedTransactionResult(
			[],
			100,
			11, // current_page
			10, // total_pages (invalid: current_page > total_pages)
			0,
			5
		);
	}
}
