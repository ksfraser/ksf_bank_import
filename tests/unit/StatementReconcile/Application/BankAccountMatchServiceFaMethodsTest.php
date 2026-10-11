<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Namespace-scoped stubs for FA global functions used in BankAccountMatchService.
// These definitions live in the PRODUCTION namespace so the production class
// (Ksfraser\FaBankImport\StatementReconcile\Application\BankAccountMatchService)
// resolves unqualified function calls to these stubs instead of the (absent)
// global FA functions.
//
// PHP function resolution: when an unqualified call like db_query() is made
// from within a namespace, PHP first looks for a namespace-level definition
// before falling back to the root namespace.
// ---------------------------------------------------------------------------

namespace Ksfraser\FaBankImport\StatementReconcile\Application {

    if (!defined('Ksfraser\\FaBankImport\\StatementReconcile\\Application\\TB_PREF')) {
        define('Ksfraser\\FaBankImport\\StatementReconcile\\Application\\TB_PREF', '0_');
    }

    if (!function_exists('Ksfraser\\FaBankImport\\StatementReconcile\\Application\\db_query')) {
        function db_query(string $sql, string $msg = '', bool $noexit = false): mixed
        {
            // Return a sentinel value; db_fetch will dispatch from a global queue.
            return $GLOBALS['_bams_db_query_return'] ?? false;
        }

        function db_fetch(mixed $result): mixed
        {
            if (!empty($GLOBALS['_bams_db_fetch_queue'])) {
                return array_shift($GLOBALS['_bams_db_fetch_queue']);
            }
            return false;
        }

        function db_escape(mixed $value): string
        {
            return "'" . addslashes((string) $value) . "'";
        }
    }

} // end namespace Ksfraser\FaBankImport\StatementReconcile\Application

// ---------------------------------------------------------------------------
// Test class in the tests namespace.
// ---------------------------------------------------------------------------

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application {

use Ksfraser\FaBankImport\StatementReconcile\Application\BankAccountMatchService;
use PHPUnit\Framework\TestCase;

/**
 * Tests that exercise the REAL (non-overridden) loadFaBankAccounts() and
 * loadHistoryMap() methods in BankAccountMatchService using namespace-scoped
 * FA function stubs defined above.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\BankAccountMatchService
 */
class BankAccountMatchServiceFaMethodsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the stub state before each test.
        $GLOBALS['_bams_db_query_return']  = true;   // truthy result object
        $GLOBALS['_bams_db_fetch_queue']   = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['_bams_db_query_return'] = false;
        $GLOBALS['_bams_db_fetch_queue']  = [];
    }

    /**
     * A direct subclass that exposes loadFaBankAccounts / loadHistoryMap as
     * public methods, but does NOT override them (so the REAL parent code runs).
     */
    private function makeRealService(float $minScore = 0.5): object
    {
        return new class (['sr_account_match_min_score' => $minScore]) extends BankAccountMatchService {
            public function callLoadFaBankAccounts(): array
            {
                return $this->loadFaBankAccounts();
            }

            public function callLoadHistoryMap(string $id): array
            {
                return $this->loadHistoryMap($id);
            }
        };
    }

    // ------------------------------------------------------------------
    // loadFaBankAccounts – happy path with rows
    // ------------------------------------------------------------------

    public function testLoadFaBankAccountsReturnsRowsFromDbFetch(): void
    {
        $GLOBALS['_bams_db_query_return'] = true;
        $GLOBALS['_bams_db_fetch_queue']  = [
            ['id' => 1, 'bank_account_name' => 'RBC Chequing', 'bank_account_number' => '1234', 'bank_name' => 'RBC'],
            ['id' => 2, 'bank_account_name' => 'TD Savings',   'bank_account_number' => '5678', 'bank_name' => 'TD'],
        ];

        $svc     = $this->makeRealService();
        $results = $svc->callLoadFaBankAccounts();

        $this->assertCount(2, $results);
        $this->assertSame('RBC Chequing', $results[0]['bank_account_name']);
    }

    // ------------------------------------------------------------------
    // loadFaBankAccounts – empty result
    // ------------------------------------------------------------------

    public function testLoadFaBankAccountsReturnsEmptyWhenNoRows(): void
    {
        $GLOBALS['_bams_db_query_return'] = true;
        $GLOBALS['_bams_db_fetch_queue']  = [];

        $svc     = $this->makeRealService();
        $results = $svc->callLoadFaBankAccounts();

        $this->assertSame([], $results);
    }

    // ------------------------------------------------------------------
    // loadHistoryMap – db_query returns false (no previous matches)
    // ------------------------------------------------------------------

    public function testLoadHistoryMapReturnsFalseWhenQueryFails(): void
    {
        $GLOBALS['_bams_db_query_return'] = false;

        $svc = $this->makeRealService();
        $map = $svc->callLoadHistoryMap('1234');

        $this->assertSame([], $map);
    }

    // ------------------------------------------------------------------
    // loadHistoryMap – rows returned
    // ------------------------------------------------------------------

    public function testLoadHistoryMapReturnsKeyedMapFromRows(): void
    {
        $GLOBALS['_bams_db_query_return'] = true;
        $GLOBALS['_bams_db_fetch_queue']  = [
            ['bank_account_id' => 3],
            ['bank_account_id' => 7],
        ];

        $svc = $this->makeRealService();
        $map = $svc->callLoadHistoryMap('1234');

        $this->assertArrayHasKey(3, $map);
        $this->assertArrayHasKey(7, $map);
        $this->assertTrue($map[3]);
    }
}

} // end namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application
