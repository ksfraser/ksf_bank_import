<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Ksfraser\FaBankImport\Services\CustomerInvoiceAllocator;
use PHPUnit\Framework\TestCase;

class CustomerInvoiceAllocatorTest extends TestCase
{
	public function testPrefersLatestExactInvoiceMatch(): void
	{
		$allocator = new CustomerInvoiceAllocator();
		$allocations = $allocator->resolveAllocations([
			['invoice_no' => 1001, 'tran_date' => '2026-05-01', 'amount' => 50.00],
			['invoice_no' => 1002, 'tran_date' => '2026-05-08', 'amount' => 50.00],
		], 50.00, '2026-05-09');

		$this->assertCount(1, $allocations);
		$this->assertSame(1002, $allocations[0]['invoice_no']);
		$this->assertEqualsWithDelta(50.00, $allocations[0]['amount'], 0.001);
	}

	public function testFindsRecentExactSumCombination(): void
	{
		$allocator = new CustomerInvoiceAllocator();
		$allocations = $allocator->resolveAllocations([
			['invoice_no' => 2001, 'tran_date' => '2026-05-01', 'amount' => 50.00],
			['invoice_no' => 2002, 'tran_date' => '2026-05-08', 'amount' => 60.00],
			['invoice_no' => 2003, 'tran_date' => '2026-04-20', 'amount' => 40.00],
		], 110.00, '2026-05-09');

		$this->assertCount(2, $allocations);
		$this->assertSame([2002, 2001], array_column($allocations, 'invoice_no'));
		$this->assertEqualsWithDelta(110.00, array_sum(array_column($allocations, 'amount')), 0.001);
	}

	public function testReturnsEmptyArrayWhenNoExactAllocationExists(): void
	{
		$allocator = new CustomerInvoiceAllocator();
		$allocations = $allocator->resolveAllocations([
			['invoice_no' => 3001, 'tran_date' => '2026-05-01', 'amount' => 50.00],
			['invoice_no' => 3002, 'tran_date' => '2026-05-08', 'amount' => 60.00],
		], 115.00, '2026-05-09');

		$this->assertSame([], $allocations);
	}
}