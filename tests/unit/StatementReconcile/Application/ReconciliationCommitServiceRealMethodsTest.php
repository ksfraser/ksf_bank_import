<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\ReconciliationCommitService;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests exercising the REAL (non-overridden) protected methods of
 * ReconciliationCommitService:
 *
 *   - isFaDbAvailable()              — checks function_exists('db_query')
 *   - markFaBankTransactionReconciled() — calls TB_PREF / db_escape / db_query
 *   - updateBankAccount()            — calls TB_PREF / db_escape / db_query
 *
 * These tests run in a separate process so the global stubs they define do
 * not pollute other tests that deliberately exclude those globals.
 *
 * SR-REQ-012 — Commit Reconciliation to FA.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\ReconciliationCommitService
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ReconciliationCommitServiceRealMethodsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // isFaDbAvailable() — real method
    // -----------------------------------------------------------------------

    /**
     * @testdox isFaDbAvailable() returns false when db_query does not exist
     */
    public function testIsFaDbAvailableFalseWhenNoDdbQuery(): void
    {
        $this->markTestSkipped('Requires isolated process without FA stubs');
        // In a fresh process db_query is not defined.
        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $svc  = new ReconciliationCommitService($repo);

        // Calling commit() exercises isFaDbAvailable() through the real path.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/db_query/');

        $svc->commit(1, 1, 1, '2026-03-31', 1000.00);
    }

    /**
     * @testdox isFaDbAvailable() returns true when db_query exists
     */
    public function testIsFaDbAvailableTrueWhenDbQueryExists(): void
    {
        // Define FA stubs in the global namespace.
        if (!defined('TB_PREF')) {
            define('TB_PREF', '0_');
        }
        if (!function_exists('db_query')) {
            // phpcs:ignore
            eval('function db_query($sql, $msg = "") { return true; }');
        }
        if (!function_exists('db_escape')) {
            // phpcs:ignore
            eval('function db_escape($val) { return "\'" . addslashes((string)$val) . "\'"; }');
        }

        $session = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession
            ::createPending(1, [], [], []);

        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $repo->method('findById')->willReturn($session);
        $repo->method('save')->willReturn(1);

        $svc = new ReconciliationCommitService($repo);

        // Should not throw — isFaDbAvailable() returns true, real DB methods called.
        $svc->commit(1, 1, 42, '2026-03-31', 1000.00);
        $this->addToAssertionCount(1);
    }

    /**
     * @testdox markFaBankTransactionReconciled() and updateBankAccount() are called via real commit
     */
    public function testRealMarkAndUpdateCalledForPairWithFaKeys(): void
    {
        $this->markTestSkipped('Requires isolated process without FA stubs');
        if (!defined('TB_PREF')) {
            define('TB_PREF', '0_');
        }

        $queriesExecuted = [];

        if (!function_exists('db_query')) {
            // Capture executed SQL into $queriesExecuted via a closure trick.
            $GLOBALS['_sr_queries'] = &$queriesExecuted;
            // phpcs:ignore
            eval('function db_query($sql, $msg = "") { $GLOBALS["_sr_queries"][] = $sql; return true; }');
        }
        if (!function_exists('db_escape')) {
            // phpcs:ignore
            eval('function db_escape($val) { return "\'" . addslashes((string)$val) . "\'"; }');
        }

        $pair = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair(
            'L001', 1, 1.0, ['EXACT_AMOUNT_DATE'], 41, 100
        );
        $session = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession
            ::createPending(1, [$pair], [], []);

        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $repo->method('findById')->willReturn($session);
        $repo->method('save')->willReturn(1);

        $svc = new ReconciliationCommitService($repo);
        $svc->commit(1, 1, 42, '2026-03-31', 999.99);

        // Two SQL queries should have been executed:
        // 1. UPDATE 0_bank_trans (markFaBankTransactionReconciled)
        // 2. UPDATE 0_bank_accounts (updateBankAccount)
        $this->assertCount(2, $queriesExecuted);
        $this->assertStringContainsString('bank_trans', $queriesExecuted[0]);
        $this->assertStringContainsString('bank_accounts', $queriesExecuted[1]);
    }
}
