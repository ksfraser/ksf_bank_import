<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\ReconciliationCommitService;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\ReconciliationCommitService
 */
class ReconciliationCommitServiceTest extends TestCase
{
    // ------------------------------------------------------------------
    // Test-double factory
    // ------------------------------------------------------------------

    /**
     * Build a testable subclass: we can control whether the FA DB is "available",
     * and the actual DB helper calls are stubbed out so no real DB is needed.
     *
     * @param bool                                      $dbAvailable
     * @param ReconciliationSessionRepositoryInterface  $repo
     * @return ReconciliationCommitService
     */
    private function makeService(
        bool $dbAvailable,
        ReconciliationSessionRepositoryInterface $repo
    ): ReconciliationCommitService {
        return new class ($repo, $dbAvailable) extends ReconciliationCommitService {
            private bool $dbAvail;

            public function __construct(
                ReconciliationSessionRepositoryInterface $repo,
                bool $dbAvail
            ) {
                parent::__construct($repo);
                $this->dbAvail = $dbAvail;
            }

            protected function isFaDbAvailable(): bool
            {
                return $this->dbAvail;
            }

            protected function markFaBankTransactionReconciled(
                int    $faTransType,
                int    $faTransNo,
                string $reconciledDate
            ): void {
                // Stubbed – no DB access in tests.
            }

            protected function updateBankAccount(
                int    $bankAccountId,
                string $statementEndDate,
                float  $closingBalance
            ): void {
                // Stubbed – no DB access in tests.
            }
        };
    }

    private function makeRepo(?ReconciliationSession $session): ReconciliationSessionRepositoryInterface
    {
        $mock = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $mock->method('findById')->willReturn($session);
        return $mock;
    }

    // ------------------------------------------------------------------
    // FA bootstrap guard
    // ------------------------------------------------------------------

    public function testThrowsRuntimeExceptionWhenFaDbUnavailable(): void
    {
        $svc = $this->makeService(false, $this->makeRepo(null));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/db_query/');

        $svc->commit(1, 1, 1, '2026-03-31', 1000.00);
    }

    // ------------------------------------------------------------------
    // Session-not-found guard
    // ------------------------------------------------------------------

    public function testThrowsReconciliationExceptionWhenSessionNotFound(): void
    {
        $svc = $this->makeService(true, $this->makeRepo(null));

        $this->expectException(ReconciliationException::class);

        $svc->commit(99, 1, 1, '2026-03-31', 1000.00);
    }

    // ------------------------------------------------------------------
    // Happy path – session with no matched pairs
    // ------------------------------------------------------------------

    public function testCommitApprovedSessionWithNoPairs(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);

        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $repo->method('findById')->willReturn($session);
        $repo->expects($this->once())->method('save')->willReturn(1);

        $svc = $this->makeService(true, $repo);

        // Should not throw.
        $svc->commit(1, 1, 42, '2026-03-31', 1000.00);

        $this->addToAssertionCount(1); // reached this point without exception
    }

    // ------------------------------------------------------------------
    // Constructor
    // ------------------------------------------------------------------

    public function testConstructorAcceptsSessionRepository(): void
    {
        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $svc  = $this->makeService(false, $repo);

        $this->assertInstanceOf(ReconciliationCommitService::class, $svc);
    }

    // ------------------------------------------------------------------
    // Lines 78-90: commit loops through pairs with FA keys.
    // ------------------------------------------------------------------

    public function testCommitCallsMarkReconciledForPairsWithFaKeys(): void
    {
        $pair    = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair(
            'L001', 1, 0.95, ['EXACT_AMOUNT_DATE'], 41, 100
        );
        $session = ReconciliationSession::createPending(1, [$pair], [], []);

        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $repo->method('findById')->willReturn($session);
        $repo->expects($this->once())->method('save')->willReturn(1);

        $markedCalls  = [];
        $updatedCalls = [];

        $svc = new class ($repo, true, $markedCalls, $updatedCalls) extends ReconciliationCommitService {
            private bool $dbAvail;
            /** @var array */
            private $marked;
            /** @var array */
            private $updated;

            public function __construct(
                ReconciliationSessionRepositoryInterface $repo,
                bool $dbAvail,
                array &$marked,
                array &$updated
            ) {
                parent::__construct($repo);
                $this->dbAvail = $dbAvail;
                $this->marked  = &$marked;
                $this->updated = &$updated;
            }

            protected function isFaDbAvailable(): bool { return $this->dbAvail; }

            protected function markFaBankTransactionReconciled(int $type, int $no, string $date): void
            {
                $this->marked[] = [$type, $no, $date];
            }

            protected function updateBankAccount(int $acctId, string $endDate, float $bal): void
            {
                $this->updated[] = [$acctId, $endDate, $bal];
            }
        };

        $svc->commit(1, 1, 42, '2026-03-31', 1000.00);

        $this->assertCount(1, $markedCalls, 'markFaBankTransactionReconciled called once');
        $this->assertSame([41, 100, '2026-03-31'], $markedCalls[0]);
        $this->assertCount(1, $updatedCalls, 'updateBankAccount called once');
        $this->assertSame([42, '2026-03-31', 1000.00], $updatedCalls[0]);
    }

    // ------------------------------------------------------------------
    // Line 112: pair with null FA keys logs and skips markReconciled.
    // ------------------------------------------------------------------

    public function testCommitSkipsPairsWithNullFaKeys(): void
    {
        // Pair WITHOUT FA keys (faTransType = null, faTransNo = null).
        $pair    = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair(
            'L002', 2, 0.95, ['EXACT_AMOUNT_DATE']
        );
        $session = ReconciliationSession::createPending(1, [$pair], [], []);

        $repo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $repo->method('findById')->willReturn($session);
        $repo->expects($this->once())->method('save')->willReturn(1);

        $markedCalls = [];

        $svc = new class ($repo, true, $markedCalls) extends ReconciliationCommitService {
            private bool $dbAvail;
            /** @var array */
            private $marked;

            public function __construct(
                ReconciliationSessionRepositoryInterface $repo,
                bool $dbAvail,
                array &$marked
            ) {
                parent::__construct($repo);
                $this->dbAvail = $dbAvail;
                $this->marked  = &$marked;
            }

            protected function isFaDbAvailable(): bool { return $this->dbAvail; }

            protected function markFaBankTransactionReconciled(int $type, int $no, string $date): void
            {
                $this->marked[] = [$type, $no, $date];
            }

            protected function updateBankAccount(int $acctId, string $endDate, float $bal): void {}
        };

        $svc->commit(1, 1, 42, '2026-03-31', 500.00);

        $this->assertCount(0, $markedCalls, 'No markFaBankTransactionReconciled for null-key pair');
    }
}
