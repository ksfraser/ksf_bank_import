<?php
namespace Ksfraser\FaBankImport\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\QueryFilters;
use DateTimeImmutable;

/**
 * Test QueryFilters DTO: immutable filter criteria for dashboard queries.
 * Supports filtering by date range, amount range, status, account code, and search.
 */
class QueryFiltersTest extends TestCase
{
    /**
     * @test
     * QueryFilters creates from array with all filter fields
     */
    public function test_create_from_array_with_all_filters(): void
    {
        $data = [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'min_amount' => 100.00,
            'max_amount' => 5000.00,
            'status' => 'PENDING',
            'account_code' => '1000',
            'search_term' => 'Customer ABC',
            'page' => 2,
            'per_page' => 25,
        ];

        $filters = QueryFilters::fromArray($data);

        $this->assertEquals('2026-04-01', $filters->startDate);
        $this->assertEquals('2026-04-30', $filters->endDate);
        $this->assertEquals(100.00, $filters->minAmount);
        $this->assertEquals(5000.00, $filters->maxAmount);
        $this->assertEquals('PENDING', $filters->status);
        $this->assertEquals('1000', $filters->accountCode);
        $this->assertEquals('Customer ABC', $filters->searchTerm);
        $this->assertEquals(2, $filters->page);
        $this->assertEquals(25, $filters->perPage);
    }

    /**
     * @test
     * QueryFilters provides default values for optional fields
     */
    public function test_default_values_for_optional_fields(): void
    {
        $filters = QueryFilters::fromArray([]);

        $this->assertNull($filters->startDate);
        $this->assertNull($filters->endDate);
        $this->assertNull($filters->minAmount);
        $this->assertNull($filters->maxAmount);
        $this->assertNull($filters->status);
        $this->assertNull($filters->accountCode);
        $this->assertNull($filters->searchTerm);
        $this->assertEquals(1, $filters->page);
        $this->assertEquals(25, $filters->perPage);
    }

    /**
     * @test
     * QueryFilters serializes to array
     */
    public function test_serialize_to_array(): void
    {
        $filters = new QueryFilters(
            startDate: '2026-04-01',
            endDate: '2026-04-30',
            minAmount: 100.00,
            maxAmount: 5000.00,
            status: 'APPROVED',
            accountCode: '2000',
            searchTerm: 'TEST',
            page: 3,
            perPage: 50
        );

        $array = $filters->toArray();

        $this->assertEquals('2026-04-01', $array['start_date']);
        $this->assertEquals('2026-04-30', $array['end_date']);
        $this->assertEquals(100.00, $array['min_amount']);
        $this->assertEquals('APPROVED', $array['status']);
        $this->assertEquals(3, $array['page']);
    }

    /**
     * @test
     * QueryFilters is immutable
     */
    public function test_is_immutable(): void
    {
        $filters = QueryFilters::fromArray([
            'status' => 'PENDING',
            'page' => 1,
        ]);

        $this->expectError(\Error::class);
        $filters->status = 'APPROVED';
    }

    /**
     * @test
     * QueryFilters hasDateRange() helper
     */
    public function test_has_date_range_helper(): void
    {
        $withDates = QueryFilters::fromArray([
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ]);

        $withoutDates = QueryFilters::fromArray([]);

        $this->assertTrue($withDates->hasDateRange());
        $this->assertFalse($withoutDates->hasDateRange());
    }

    /**
     * @test
     * QueryFilters hasAmountRange() helper
     */
    public function test_has_amount_range_helper(): void
    {
        $withAmounts = QueryFilters::fromArray([
            'min_amount' => 50.00,
            'max_amount' => 1000.00,
        ]);

        $withoutAmounts = QueryFilters::fromArray([]);

        $this->assertTrue($withAmounts->hasAmountRange());
        $this->assertFalse($withoutAmounts->hasAmountRange());
    }

    /**
     * @test
     * QueryFilters isFiltered() indicates if any filter applied
     */
    public function test_is_filtered_indicator(): void
    {
        $noFilters = QueryFilters::fromArray([]);
        $withFilter = QueryFilters::fromArray(['status' => 'PENDING']);

        $this->assertFalse($noFilters->isFiltered());
        $this->assertTrue($withFilter->isFiltered());
    }

    /**
     * @test
     * QueryFilters calculateOffset() for pagination
     */
    public function test_calculate_offset_for_pagination(): void
    {
        $page1 = QueryFilters::fromArray(['page' => 1, 'per_page' => 25]);
        $page2 = QueryFilters::fromArray(['page' => 2, 'per_page' => 25]);
        $page3 = QueryFilters::fromArray(['page' => 3, 'per_page' => 50]);

        $this->assertEquals(0, $page1->calculateOffset());
        $this->assertEquals(25, $page2->calculateOffset());
        $this->assertEquals(100, $page3->calculateOffset());
    }

    /**
     * @test
     * QueryFilters validates status field
     */
    public function test_validates_status_field(): void
    {
        $validStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE', null];

        foreach ($validStatuses as $status) {
            $filters = QueryFilters::fromArray(['status' => $status]);
            $this->assertEquals($status, $filters->status);
        }

        // Invalid status throws exception
        $this->expectException(\InvalidArgumentException::class);
        QueryFilters::fromArray(['status' => 'INVALID']);
    }
}
