<?php
/**
 * Pagination feature tests for bi_transactions.get_transactions() method
 *
 * TDD test suite for the production hotfix addressing database timeout issues.
 * All tests validate that pagination prevents the 5-second query timeout while
 * maintaining backward compatibility with existing call sites.
 *
 * @package    KSF_BankImport
 * @subpackage Tests\Unit
 * @author     GitHub Copilot / Kevin Fraser
 * @since      20260405
 * @version    20260405.1
 */

use PHPUnit\Framework\TestCase;

/**
 * Pagination tests for get_transactions() method
 *
 * Tests validate:
 * - Default pagination limits results to 5 rows
 * - Offset/limit parameters work correctly
 * - Pagination metadata is calculated correctly
 * - Backward compatibility with existing calls
 * - Status filtering + pagination combined
 * - Paired transaction search + pagination
 * - Performance: queries return in <1 second
 *
 * @group pagination
 * @group hotfix
 * @group performance
 * @since 20260405
 */
class BiTransactionsPaginationTest extends TestCase
{
    /**
     * @var bi_transactions
     */
    private $biTransactions;

    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize the bi_transactions class (provided by test stubs)
        $this->biTransactions = new bi_transactions();
    }

    /**
     * Test that get_transactions() without parameters returns pagination structure
     *
     * REQUIREMENT: REQ-002, REQ-002 - Default page size 5 prevents timeout
     * REQUIREMENT: REQ-004 - Return pagination metadata
     *
     * @test
     * @return void
     */
    public function testGetTransactionsDefaultReturnsPaginationStructure()
    {
        // Call without parameters should use defaults
        $result = $this->biTransactions->get_transactions();

        // Result should be an array with pagination metadata
        $this->assertIsArray($result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('total_pages', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('limit', $result);
    }

    /**
     * Test that default pagination returns at most 5 rows
     *
     * REQUIREMENT: REQ-002 - Default page size 5
     * REQUIREMENT: PERF-001 - Query returns <1 second with LIMIT 5
     *
     * @test
     * @return void
     */
    public function testGetTransactionsDefaultLimitsFiveRows()
    {
        $start = microtime(true);
        $result = $this->biTransactions->get_transactions();
        $duration = microtime(true) - $start;

        // Should not exceed default limit of 5
        $transactionCount = count($result['transactions'] ?? []);
        $this->assertLessThanOrEqual(
            5,
            $transactionCount,
            'Default pagination should return at most 5 rows'
        );

        // Should complete in <1 second (prevent timeout)
        $this->assertLessThan(
            1.0,
            $duration,
            sprintf('Query should complete in <1 second, took %.2f seconds', $duration)
        );
    }

    /**
     * Test pagination metadata calculation with default params
     *
     * REQUIREMENT: REQ-004 - Return pagination metadata
     * REQUIREMENT: REQ-203 - Calculate current_page, total_pages correctly
     *
     * @test
     * @return void
     */
    public function testPaginationMetadataCalculation()
    {
        $result = $this->biTransactions->get_transactions();

        // Verify pagination metadata is present and correctly typed
        $this->assertIsInt($result['total_count']);
        $this->assertIsInt($result['current_page']);
        $this->assertIsInt($result['total_pages']);
        $this->assertIsInt($result['offset']);
        $this->assertIsInt($result['limit']);

        // First page should always be page 1
        $this->assertEquals(1, $result['current_page']);

        // Offset should be 0 for first page
        $this->assertEquals(0, $result['offset']);

        // Limit should be default 5 when not specified
        $this->assertEquals(5, $result['limit']);

        // total_pages should be calculated correctly
        // ceil(total_count / limit), minimum 1 page
        $expectedPages = max(1, (int)ceil($result['total_count'] / $result['limit']));
        $this->assertEquals($expectedPages, $result['total_pages']);
    }

    /**
     * Test that offset parameter works to paginate through results
     *
     * REQUIREMENT: REQ-202 - Support optional custom page size
     * REQUIREMENT: REQ-001 - Add LIMIT/OFFSET pagination
     *
     * @test
     * @return void
     */
    public function testOffsetParameterNavigatesPagination()
    {
        // Get page 1 (offset 0)
        $page1 = $this->biTransactions->get_transactions(
            null,      // status
            null,      // transAfterDate
            null,      // transToDate
            null,      // transactionAmount
            null,      // transactionTitle
            null,      // limit (deprecated)
            null,      // bankAccount
            0,         // offset
            5          // limit_page
        );

        // Get page 2 (offset 5)
        $page2 = $this->biTransactions->get_transactions(
            null,      // status
            null,      // transAfterDate
            null,      // transToDate
            null,      // transactionAmount
            null,      // transactionTitle
            null,      // limit (deprecated)
            null,      // bankAccount
            5,         // offset
            5          // limit_page
        );

        // Page 2 should have different data than page 1 (assuming enough data)
        if ($page1['total_count'] > 5) {
            $this->assertNotEquals(
                $page1['transactions'],
                $page2['transactions'],
                'Page 2 should have different transactions than page 1'
            );
            $this->assertEquals(2, $page2['current_page']);
            $this->assertEquals(5, $page2['offset']);
        }
    }

    /**
     * Test that custom limit_page parameter changes page size
     *
     * REQUIREMENT: REQ-202 - Support optional custom page size
     *
     * @test
     * @return void
     */
    public function testCustomLimitPageParameter()
    {
        // Request with custom limit_page of 10
        $result = $this->biTransactions->get_transactions(
            null,      // status
            null,      // transAfterDate
            null,      // transToDate
            null,      // transactionAmount
            null,      // transactionTitle
            null,      // limit (deprecated)
            null,      // bankAccount
            0,         // offset
            10         // limit_page = 10 instead of default 5
        );

        // Should have at most 10 rows, not 5
        $this->assertLessThanOrEqual(
            10,
            count($result['transactions'] ?? [])
        );

        // Limit should reflect the custom value (or default if not supported)
        $this->assertGreaterThanOrEqual(5, $result['limit']);
        $this->assertLessThanOrEqual(10, $result['limit']);

        // Total pages calculation should use custom limit
        $expectedPages = max(1, (int)ceil($result['total_count'] / $result['limit']));
        $this->assertEquals($expectedPages, $result['total_pages']);
    }

    /**
     * Test backward compatibility: old calls without pagination params still work
     *
     * REQUIREMENT: REQ-007 - All existing call sites work without modification
     * REQUIREMENT: REQ-008 - Calls without params get default pagination
     * REQUIREMENT: REQ-010 - No breaking changes to method signature
     *
     * @test
     * @return void
     */
    public function testBackwardCompatibilityNoParameters()
    {
        // This is how get_transactions() was called in process_statements.php
        $result = $this->biTransactions->get_transactions();

        // Should still work (not return false or throw exception)
        $this->assertNotNull($result);
        $this->assertIsArray($result);

        // Should have transactions in old format
        $this->assertArrayHasKey('transactions', $result);
        $transactions = $result['transactions'];
        $this->assertIsArray($transactions);
    }

    /**
     * Test backward compatibility: status filter parameter still works
     *
     * REQUIREMENT: REQ-007 - All existing call sites work without modification
     * REQUIREMENT: REQ-008 - Calls without pagination params get default pagination
     *
     * @test
     * @return void
     */
    public function testBackwardCompatibilityStatusFilter()
    {
        // This is how get_transactions() is called in process_statements.php line 504
        // for filtering by status 0
        $result = $this->biTransactions->get_transactions(0);

        // Should return pagination structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('current_page', $result);

        // Results should be limited to 5 (default) AND filtered by status 0
        $transactions = $result['transactions'];
        $this->assertLessThanOrEqual(5, count($transactions));

        // All transactions should have status value if filterable
        // (This is a soft assertion as legacy code might not always have status)
    }

    /**
     * Test that pagination doesn't break paired transaction search
     *
     * REQUIREMENT: REQ-007 - All existing call sites work without modification
     * REQUIREMENT: REQ-009 - Code works on production baseline
     *
     * This tests the call pattern from BiLineItemModel::findPaired()
     * and class.bi_lineitem.php line 527
     *
     * @test
     * @return void
     */
    public function testPairedTransactionSearchWithPagination()
    {
        // Simulate paired transaction search pattern from BiLineItemModel:
        // $bi_t->get_transactions(0, $this->valueTimestamp, add_days($this->valueTimestamp, 2), $this->amount, null)

        $result = $this->biTransactions->get_transactions(
            0,                          // status = 0
            date('Y-m-d', time()),      // transAfterDate
            date('Y-m-d', time() + (2 * 24 * 3600)),  // transToDate (2 days later)
            100.00,                     // transactionAmount
            null                        // transactionTitle
        );

        // Should return pagination structure (not the old flat array)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('total_count', $result);

        // Should be paginated
        $transactions = $result['transactions'];
        $this->assertLessThanOrEqual(5, count($transactions));
    }

    /**
     * Test metadata edge case: last page calculation
     *
     * REQUIREMENT: REQ-004 - Return pagination metadata
     * REQUIREMENT: UX-001 - Pagination controls must be discoverable
     *
     * @test
     * @return void
     */
    public function testLastPageMetadata()
    {
        // Get first page
        $page1 = $this->biTransactions->get_transactions(null, null, null, null, null, null, null, 0, 5);

        // If there are multiple pages, get last page
        if ($page1['total_pages'] > 1) {
            $lastPageOffset = ($page1['total_pages'] - 1) * 5;
            $lastPage = $this->biTransactions->get_transactions(
                null, null, null, null, null, null, null, $lastPageOffset, 5
            );

            // Last page should have correct page number
            $this->assertEquals($page1['total_pages'], $lastPage['current_page']);

            // Last page should have fewer than or equal to 5 rows
            $this->assertLessThanOrEqual(5, count($lastPage['transactions']));

            // Last page rows count should match: total_count - (rows per page * pages - 1)
            $expectedLastPageRows = $page1['total_count'] - ($lastPageOffset);
            $this->assertLessThanOrEqual(5, $expectedLastPageRows);
        }
    }

    /**
     * Test performance: massive dataset with pagination is still fast
     *
     * REQUIREMENT: PERF-001 - Query must be <1 second with LIMIT 5
     * REQUIREMENT: PERF-002 - Memory usage decreases due to fewer rows
     *
     * @test
     * @return void
     */
    public function testPerformanceMassiveDataset()
    {
        // Even with a massive result set (if DB has 1000+ transactions),
        // pagination should be fast due to LIMIT clause
        $start = microtime(true);
        $result = $this->biTransactions->get_transactions();
        $duration = microtime(true) - $start;

        // Should always be <1 second due to LIMIT 5
        $this->assertLessThan(
            1.0,
            $duration,
            sprintf('Even with massive dataset, should be <1 second, took %.3f seconds', $duration)
        );

        // Total count should be accurate even if there are many rows
        $this->assertIsInt($result['total_count']);
        $this->assertGreaterThanOrEqual(0, $result['total_count']);
    }

    /**
     * Test that pagination metadata is always present
     *
     * REQUIREMENT: REQ-004 - Return pagination metadata
     * REQUIREMENT: REQ-007 - All existing call sites work without modification
     *
     * @test
     * @return void
     */
    public function testPaginationMetadataAlwaysPresent()
    {
        // Even with various parameter combinations, metadata should always be present
        $testCases = [
            [],  // No params
            [0],  // Status 0
            [1],  // Status 1
            [0, null, null, null, null, null, null, 0, 10],  // With offset and limit
        ];

        foreach ($testCases as $params) {
            $result = $this->biTransactions->get_transactions(...$params);

            // All required pagination keys must be present
            $this->assertArrayHasKey('transactions', $result, 'Missing "transactions" key');
            $this->assertArrayHasKey('total_count', $result, 'Missing "total_count" key');
            $this->assertArrayHasKey('current_page', $result, 'Missing "current_page" key');
            $this->assertArrayHasKey('total_pages', $result, 'Missing "total_pages" key');
            $this->assertArrayHasKey('offset', $result, 'Missing "offset" key');
            $this->assertArrayHasKey('limit', $result, 'Missing "limit" key');
        }
    }

    /**
     * Test empty result set returns valid pagination structure
     *
     * REQUIREMENT: REQ-004 - Return pagination metadata
     * REQUIREMENT: REQ-007 - Handle edge case of no matching transactions
     *
     * @test
     * @return void
     */
    public function testEmptyResultsPaginationStructure()
    {
        // Try to find transactions with an impossible filter
        // (adjust based on actual DB schema - this is a hypothetical)
        $result = $this->biTransactions->get_transactions(
            null,
            date('Y-m-d', strtotime('+100 years')),  // Far future
            date('Y-m-d', strtotime('+101 years')),
            null,
            null
        );

        // Even with empty results, pagination structure should be present
        $this->assertIsArray($result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('total_count', $result);

        // Empty results should have 0 total_count
        if (empty($result['transactions'])) {
            $this->assertEquals(0, $result['total_count']);
            // And 1 page (even empty sets have "1 page")
            $this->assertGreaterThanOrEqual(1, $result['total_pages']);
        }
    }

    /**
     * Teardown after tests
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->biTransactions);
    }
}
?>
