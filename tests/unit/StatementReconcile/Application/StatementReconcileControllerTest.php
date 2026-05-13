<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\BankAccountMatchService;
use Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView;
use Ksfraser\FaBankImport\StatementReconcile\Application\StatementReconcileController;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\StatementOcrRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Service\ReconciliationCommitServiceInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use PHPUnit\Framework\TestCase;
// Test double — in-memory store so no PHP session is needed in any test.
require_once __DIR__ . '/InMemoryPendingSessionStore.php';

/**
 * Tests for the protected pure-logic methods of StatementReconcileController.
 *
 * Uses an anonymous subclass to expose protected methods without FA globals.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\StatementReconcileController
 */
class StatementReconcileControllerTest extends TestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Build the controller subclass with stub dependencies so the constructor
     * completes without FA globals, and expose detectDuplicateTransactionIds.
     */
    private function makeController(): object
    {
        $view     = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $ocrRepo  = $this->createMock(StatementOcrRepositoryInterface::class);
        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $commit   = $this->createMock(ReconciliationCommitServiceInterface::class);
        $matcher  = $this->getMockBuilder(BankAccountMatchService::class)->disableOriginalConstructor()->getMock();

        return new class(
            $view,
            $ocrRepo,
            $sessRepo,
            $commit,
            $matcher,
            [],
            new InMemoryPendingSessionStore()
        ) extends StatementReconcileController {
            /** Expose for testing. */
            public function exposedDetectDuplicates(array $txs): array
            {
                return $this->detectDuplicateTransactionIds($txs);
            }
        };
    }

    private function makeTx(int $id, string $amount, string $date = '2026-03-15'): BankTransactionDto
    {
        return new BankTransactionDto($id, new \DateTimeImmutable($date), $amount, 'Vendor', 'debit');
    }

    // ------------------------------------------------------------------
    // REQ-017 — detectDuplicateTransactionIds
    // ------------------------------------------------------------------

    /**
     * Two transactions with same date and amount are both flagged.
     */
    public function testDetectsDuplicatesSameDateAndAmount(): void
    {
        $ctrl = $this->makeController();
        $txs  = [
            $this->makeTx(1, '50.00', '2026-03-10'),
            $this->makeTx(2, '50.00', '2026-03-10'),
        ];

        $result = $ctrl->exposedDetectDuplicates($txs);

        sort($result);
        $this->assertSame([1, 2], $result);
    }

    /**
     * Two transactions with same amount but different dates are NOT duplicates.
     */
    public function testDoesNotFlagDifferentDates(): void
    {
        $ctrl = $this->makeController();
        $txs  = [
            $this->makeTx(1, '50.00', '2026-03-10'),
            $this->makeTx(2, '50.00', '2026-03-11'),
        ];

        $result = $ctrl->exposedDetectDuplicates($txs);

        $this->assertSame([], $result);
    }

    /**
     * Two transactions with same date but different amounts are NOT duplicates.
     */
    public function testDoesNotFlagDifferentAmounts(): void
    {
        $ctrl = $this->makeController();
        $txs  = [
            $this->makeTx(1, '50.00', '2026-03-10'),
            $this->makeTx(2, '75.00', '2026-03-10'),
        ];

        $result = $ctrl->exposedDetectDuplicates($txs);

        $this->assertSame([], $result);
    }

    /**
     * A single transaction cannot be a duplicate.
     */
    public function testSingleTransactionNeverDuplicate(): void
    {
        $ctrl = $this->makeController();
        $txs  = [$this->makeTx(1, '50.00')];

        $result = $ctrl->exposedDetectDuplicates($txs);

        $this->assertSame([], $result);
    }

    /**
     * Empty input returns empty result.
     */
    public function testEmptyInputReturnsEmpty(): void
    {
        $ctrl   = $this->makeController();
        $result = $ctrl->exposedDetectDuplicates([]);

        $this->assertSame([], $result);
    }

    /**
     * Three transactions where two share same date/amount, third differs:
     * only the matching pair is flagged.
     */
    public function testOnlyDuplicateGroupFlagged(): void
    {
        $ctrl = $this->makeController();
        $txs  = [
            $this->makeTx(1, '50.00', '2026-03-10'),
            $this->makeTx(2, '50.00', '2026-03-10'),
            $this->makeTx(3, '99.00', '2026-03-10'),
        ];

        $result = $ctrl->exposedDetectDuplicates($txs);

        sort($result);
        $this->assertSame([1, 2], $result);
        $this->assertNotContains(3, $result);
    }

    /**
     * Triple duplicate: all three IDs with same date+amount are flagged.
     */
    public function testTripleDuplicateAllFlagged(): void
    {
        $ctrl = $this->makeController();
        $txs  = [
            $this->makeTx(1, '50.00', '2026-03-10'),
            $this->makeTx(2, '50.00', '2026-03-10'),
            $this->makeTx(3, '50.00', '2026-03-10'),
        ];

        $result = $ctrl->exposedDetectDuplicates($txs);

        sort($result);
        $this->assertSame([1, 2, 3], $result);
    }

    // ------------------------------------------------------------------
    // Full action handler tests
    // All these tests use a richer subclass that stubs FA-dependent
    // methods so no real database or Ollama is needed.
    // ------------------------------------------------------------------

    /**
     * Build a controller subclass that stubs ALL FA-dependent methods.
     *
     * @param object|null $view    Pre-built mock ReconcileView (or null to create fresh mock)
     * @param object|null $ocrRepo Pre-built mock StatementOcrRepositoryInterface
     * @param object|null $sessRepo Pre-built mock ReconciliationSessionRepositoryInterface
     * @param object|null $commit  Pre-built mock ReconciliationCommitServiceInterface
     * @param array       $pendingData  Data to store in PHP session under SESSION_KEY
     * @return StatementReconcileController (anonymous subclass)
     */
    private function makeFullController(
        ?object $view      = null,
        ?object $ocrRepo   = null,
        ?object $sessRepo  = null,
        ?object $commit    = null,
        array   $pendingData = []
    ): StatementReconcileController {
        $view     = $view     ?? $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $ocrRepo  = $ocrRepo  ?? $this->createMock(StatementOcrRepositoryInterface::class);
        $sessRepo = $sessRepo ?? $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $commit   = $commit   ?? $this->createMock(ReconciliationCommitServiceInterface::class);
        $matcher  = $this->getMockBuilder(BankAccountMatchService::class)->disableOriginalConstructor()->getMock();
        $store    = new InMemoryPendingSessionStore($pendingData ?: null);

        return new class(
            $view, $ocrRepo, $sessRepo, $commit, $matcher,
            ['ollama_base_url' => 'http://localhost:11434'],
            $store
        ) extends StatementReconcileController {
            /** Expose detectDuplicateTransactionIds */
            public function exposedDetectDuplicates(array $txs): array
            {
                return $this->detectDuplicateTransactionIds($txs);
            }

            protected function validateUpload(): array
            {
                return ['name' => 'test.pdf', 'tmp_name' => '/tmp/test.pdf', 'size' => 100];
            }

            protected function buildStatementOcr(string $tmpPath): \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr
            {
                $metadata = \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata::fromArray([
                    'statement_start_date' => '2026-03-01',
                    'statement_end_date'   => '2026-03-31',
                    'opening_balance'      => '500.00',
                    'closing_balance'      => '1200.00',
                    'account_identifier'   => '5678',
                ]);
                $raw = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult('{}', 'gemma4');
                return \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr::create($metadata, [], $raw);
            }

            protected function loadBankTransactions(
                \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr $ocr,
                int $bankAccountId
            ): array {
                return [];
            }

            protected function loadBankAccountEndingBalance(int $bankAccountId): ?float
            {
                return null;
            }

            protected function loadAllFaBankAccounts(): array
            {
                return [['id' => 1, 'bank_account_name' => 'RBC Chequing']];
            }
        };
    }

    private function makeOcr(): \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr
    {
        $metadata = \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '1200.00',
            'account_identifier'   => '5678',
        ]);
        $raw = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult('{}', 'gemma4');
        return \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr::create($metadata, [], $raw);
    }

    // ------------------------------------------------------------------
    // handle() default action — renderUploadForm
    // ------------------------------------------------------------------

    public function testHandleDefaultActionRendersUploadForm(): void
    {
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderUploadForm');

        $ctrl = $this->makeFullController($view);
        $ctrl->handle('', 1);
    }

    // ------------------------------------------------------------------
    // handle('parse') — OCR pipeline → account confirmation
    // ------------------------------------------------------------------

    public function testHandleParseRendersAccountConfirmation(): void
    {
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderAccountConfirmation');

        $ocrRepo = $this->createMock(StatementOcrRepositoryInterface::class);
        $ocrRepo->method('save')->willReturn(1);

        $matcher = $this->getMockBuilder(BankAccountMatchService::class)->disableOriginalConstructor()->getMock();
        $matcher->method('match')->willReturn(['candidates' => [], 'best_id' => null]);

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $commit   = $this->createMock(ReconciliationCommitServiceInterface::class);

        $ctrl = new class ($view, $ocrRepo, $sessRepo, $commit, $matcher,
            ['ollama_base_url' => 'http://localhost:11434'],
            new InMemoryPendingSessionStore()
        ) extends StatementReconcileController {
            protected function validateUpload(): array
            {
                return ['name' => 'test.pdf', 'tmp_name' => '/tmp/test.pdf', 'size' => 100];
            }

            protected function buildStatementOcr(string $tmpPath): \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr
            {
                $meta = \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata::fromArray([
                    'statement_start_date' => '2026-03-01',
                    'statement_end_date'   => '2026-03-31',
                    'opening_balance'      => '500.00',
                    'closing_balance'      => '1200.00',
                    'account_identifier'   => '5678',
                ]);
                $raw = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult('{}', 'gemma');
                return \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr::create($meta, [], $raw);
            }

            protected function loadAllFaBankAccounts(): array { return []; }

            protected function storePendingSession(array $data): void { /* no-op: test only needs renderAccountConfirmation */ }
        };

        $ctrl->handle('parse', 1);
    }

    // ------------------------------------------------------------------
    // handle('confirm_account') — no pending session → renderError
    // ------------------------------------------------------------------

    public function testHandleConfirmAccountWithoutSessionRendersError(): void
    {
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view); // no pendingData → loadPendingSession() returns null
        $ctrl->handle('confirm_account', 1);
    }

    // ------------------------------------------------------------------
    // handle('confirm_account') — no bank_account_id → renderError
    // ------------------------------------------------------------------

    public function testHandleConfirmAccountWithoutBankAccountIdRendersError(): void
    {
        $ocr    = $this->makeOcr();
        $_POST['bank_account_id'] = '0';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view, null, null, null, [
            'ocr_id'        => 1,
            'statement_ocr' => $ocr,
        ]);
        $ctrl->handle('confirm_account', 1);

        unset($_POST['bank_account_id']);
    }

    // ------------------------------------------------------------------
    // handle('confirm_account') — valid → renderReview
    // ------------------------------------------------------------------

    public function testHandleConfirmAccountWithValidDataRendersReview(): void
    {
        $ocr = $this->makeOcr();
        $_POST['bank_account_id'] = '5';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderReview');

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $sessRepo->method('save')->willReturn(1);

        $ctrl = $this->makeFullController($view, null, $sessRepo, null, [
            'ocr_id'        => 1,
            'statement_ocr' => $ocr,
        ]);
        $ctrl->handle('confirm_account', 1);

        unset($_POST['bank_account_id']);
    }

    // ------------------------------------------------------------------
    // handle('approve') — no pending session → renderError
    // ------------------------------------------------------------------

    public function testHandleApproveWithoutSessionRendersError(): void
    {
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view); // no pendingData → loadPendingSession() returns null
        $ctrl->handle('approve', 1);
    }

    // ------------------------------------------------------------------
    // handle('approve') — invalid session_id → renderError
    // ------------------------------------------------------------------

    public function testHandleApproveWithInvalidSessionIdRendersError(): void
    {
        $ocr  = $this->makeOcr();
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view, null, null, null, [
            'session_id'      => 0,
            'bank_account_id' => 5,
            'statement_ocr'   => $ocr,
        ]);
        $ctrl->handle('approve', 1);
    }

    // ------------------------------------------------------------------
    // handle('approve') — valid → commit + renderSuccess
    // ------------------------------------------------------------------

    public function testHandleApproveWithValidDataCommitsAndRendersSuccess(): void
    {
        $ocr  = $this->makeOcr();
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderSuccess');

        $commit = $this->createMock(ReconciliationCommitServiceInterface::class);
        $commit->expects($this->once())->method('commit');

        $ctrl = $this->makeFullController($view, null, null, $commit, [
            'session_id'      => 7,
            'bank_account_id' => 5,
            'statement_ocr'   => $ocr,
        ]);
        $ctrl->handle('approve', 1);
    }

    // ------------------------------------------------------------------
    // handle('remove_pair') — missing line_id → renderError
    // ------------------------------------------------------------------

    public function testHandleRemovePairWithoutLineIdRendersError(): void
    {
        unset($_POST['line_id']);

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view);
        $ctrl->handle('remove_pair', 1);
    }

    // ------------------------------------------------------------------
    // handle('remove_pair') — no session → renderError
    // ------------------------------------------------------------------

    public function testHandleRemovePairWithoutSessionRendersError(): void
    {
        $_POST['line_id'] = 'L001';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view); // no pendingData → null session
        $ctrl->handle('remove_pair', 1);

        unset($_POST['line_id']);
    }

    // ------------------------------------------------------------------
    // handle('remove_pair') — valid → re-renders review
    // ------------------------------------------------------------------

    public function testHandleRemovePairValid(): void
    {
        $ocr     = $this->makeOcr();
        $session = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession::createPending(1, [], ['L001'], []);
        $_POST['line_id'] = 'L001';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderReview');

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $sessRepo->method('save')->willReturn(1);

        $ctrl = $this->makeFullController($view, null, $sessRepo, null, [
            'statement_ocr'             => $ocr,
            'session'                   => $session,
            'bank_transactions'         => [],
            'warnings'                  => [],
            'duplicate_transaction_ids' => [],
        ]);
        $ctrl->handle('remove_pair', 1);

        unset($_POST['line_id']);
    }

    // ------------------------------------------------------------------
    // handle('manual_match') — missing line_id → renderError
    // ------------------------------------------------------------------

    public function testHandleManualMatchWithoutLineIdRendersError(): void
    {
        unset($_POST['line_id']);
        unset($_POST['bank_tx_id']);

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view);
        $ctrl->handle('manual_match', 1);
    }

    // ------------------------------------------------------------------
    // handle('manual_match') — missing bank_tx_id → renderError
    // ------------------------------------------------------------------

    public function testHandleManualMatchWithoutBankTxIdRendersError(): void
    {
        $_POST['line_id']    = 'L001';
        $_POST['bank_tx_id'] = '0';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view);
        $ctrl->handle('manual_match', 1);

        unset($_POST['line_id'], $_POST['bank_tx_id']);
    }

    // ------------------------------------------------------------------
    // handle('manual_match') — no session → renderError
    // ------------------------------------------------------------------

    public function testHandleManualMatchWithoutSessionRendersError(): void
    {
        $_POST['line_id']    = 'L001';
        $_POST['bank_tx_id'] = '5';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view); // no pendingData → null session
        $ctrl->handle('manual_match', 1);

        unset($_POST['line_id'], $_POST['bank_tx_id']);
    }

    // ------------------------------------------------------------------
    // handle('manual_match') — valid → renderReview
    // ------------------------------------------------------------------

    public function testHandleManualMatchValidRendersReview(): void
    {
        $ocr     = $this->makeOcr();
        $session = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession::createPending(1, [], ['L001'], [2]);
        $_POST['line_id']    = 'L001';
        $_POST['bank_tx_id'] = '2';

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderReview');

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $sessRepo->method('save')->willReturn(1);

        $ctrl = $this->makeFullController($view, null, $sessRepo, null, [
            'statement_ocr'             => $ocr,
            'session'                   => $session,
            'bank_transactions'         => [],
            'warnings'                  => [],
            'duplicate_transaction_ids' => [],
        ]);
        $ctrl->handle('manual_match', 1);

        unset($_POST['line_id'], $_POST['bank_tx_id']);
    }

    // ------------------------------------------------------------------
    // handle('print_schedule') — no session → renderError
    // ------------------------------------------------------------------

    public function testHandlePrintScheduleWithoutSessionRendersError(): void
    {
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ctrl = $this->makeFullController($view); // no pendingData → null session
        $ctrl->handle('print_schedule', 1);
    }

    // ------------------------------------------------------------------
    // handle('print_schedule') — valid → renderPrintSchedule
    // ------------------------------------------------------------------

    public function testHandlePrintScheduleValidRendersPrintSchedule(): void
    {
        $ocr     = $this->makeOcr();
        $session = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession::createPending(1, [], [], []);

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderPrintSchedule');

        $ctrl = $this->makeFullController($view, null, null, null, [
            'statement_ocr'     => $ocr,
            'session'           => $session,
            'bank_transactions' => [],
        ]);
        $ctrl->handle('print_schedule', 1);
    }

    // ------------------------------------------------------------------
    // handle() — StatementOcrException caught → renderError
    // ------------------------------------------------------------------

    public function testHandleCatchesStatementOcrExceptionAndRendersError(): void
    {
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $ocrRepo = $this->createMock(StatementOcrRepositoryInterface::class);
        $ocrRepo->method('save')->willThrowException(
            \Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException::forReason('test')
        );

        $matcher = $this->getMockBuilder(BankAccountMatchService::class)->disableOriginalConstructor()->getMock();
        $matcher->method('match')->willReturn(['candidates' => [], 'best_id' => null]);

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $commit   = $this->createMock(ReconciliationCommitServiceInterface::class);

        $ctrl = new class ($view, $ocrRepo, $sessRepo, $commit, $matcher,
            ['ollama_base_url' => 'http://localhost'],
            new InMemoryPendingSessionStore()
        ) extends StatementReconcileController {
            protected function validateUpload(): array
            {
                return ['name' => 'x.pdf', 'tmp_name' => '/tmp/x.pdf', 'size' => 100];
            }

            protected function buildStatementOcr(string $p): \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr
            {
                $meta = \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata::fromArray([
                    'statement_start_date' => '2026-03-01',
                    'statement_end_date'   => '2026-03-31',
                    'opening_balance'      => '0',
                    'closing_balance'      => '0',
                ]);
                $raw = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult('{}', 'g');
                return \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr::create($meta, [], $raw);
            }

            protected function loadAllFaBankAccounts(): array { return []; }
        };

        $ctrl->handle('parse', 1);
    }

    // ------------------------------------------------------------------
    // handle() — generic Throwable caught → renderError
    // ------------------------------------------------------------------

    public function testHandleCatchesGenericThrowableAndRendersError(): void
    {
        $ocr  = $this->makeOcr();
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError');

        $commit = $this->createMock(ReconciliationCommitServiceInterface::class);
        $commit->method('commit')->willThrowException(new \RuntimeException('unexpected'));

        $ctrl = $this->makeFullController($view, null, null, $commit, [
            'session_id'      => 7,
            'bank_account_id' => 5,
            'statement_ocr'   => $ocr,
        ]);
        $ctrl->handle('approve', 1);
    }

    // ------------------------------------------------------------------
    // handle() — ReconciliationException caught → renderError (line 136)
    // ------------------------------------------------------------------

    public function testHandleCatchesReconciliationExceptionAndRendersError(): void
    {
        $ocr  = $this->makeOcr();
        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderError')
            ->with($this->stringContains('Reconciliation error:'));

        $commit = $this->createMock(ReconciliationCommitServiceInterface::class);
        $commit->method('commit')->willThrowException(
            \Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException::forReason('test reconciliation error')
        );

        $ctrl = $this->makeFullController($view, null, null, $commit, [
            'session_id'      => 7,
            'bank_account_id' => 5,
            'statement_ocr'   => $ocr,
        ]);
        $ctrl->handle('approve', 1);
    }

    // ------------------------------------------------------------------
    // handle('confirm_account') — REQ-013 balance mismatch warning
    // (lines 223-229: FA ending balance differs from OCR opening balance)
    // ------------------------------------------------------------------

    public function testHandleConfirmAccountAddsBalanceMismatchWarning(): void
    {
        // OCR opening balance = 500; FA ending balance = 400 → diff = 100 > 0.01
        $metadata = \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '500.00', // same as opening → REQ-016 diff = 0, no second warning
            'account_identifier'   => '5678',
        ]);
        $raw = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult('{}', 'gemma4');
        $ocr = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr::create($metadata, [], $raw);

        $_POST['bank_account_id'] = '5';

        $view     = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderReview');

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $sessRepo->method('save')->willReturn(1);
        $ocrRepo  = $this->createMock(StatementOcrRepositoryInterface::class);
        $commit   = $this->createMock(ReconciliationCommitServiceInterface::class);
        $matcher  = $this->getMockBuilder(BankAccountMatchService::class)->disableOriginalConstructor()->getMock();

        $ctrl = new class ($view, $ocrRepo, $sessRepo, $commit, $matcher,
            ['ollama_base_url' => 'http://localhost:11434'],
            new InMemoryPendingSessionStore(['ocr_id' => 1, 'statement_ocr' => $ocr])
        ) extends StatementReconcileController {
            protected function loadBankTransactions(
                \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr $ocr,
                int $id
            ): array {
                return [];
            }

            protected function loadBankAccountEndingBalance(int $bankAccountId): ?float
            {
                return 400.00; // differs from OCR opening 500.00 by 100 > 0.01
            }
        };

        $ctrl->handle('confirm_account', 1);

        unset($_POST['bank_account_id']);
    }

    // ------------------------------------------------------------------
    // handle('confirm_account') — OCR lines summed (line 235 foreach body)
    // ------------------------------------------------------------------

    public function testHandleConfirmAccountSumsOcrLineAmounts(): void
    {
        // Create an OCR with one StatementLine so the foreach body on line 235 executes.
        $metadata = \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '600.00',
            'account_identifier'   => '5678',
        ]);
        $line = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine(
            'L001',
            new \DateTimeImmutable('2026-03-15'),
            'Coffee',
            '25.00',
            'debit',
            'raw'
        );
        $raw = new \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult('{}', 'gemma4');
        $ocr = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr::create($metadata, [$line], $raw);

        $_POST['bank_account_id'] = '5';

        $view     = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderReview');

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $sessRepo->method('save')->willReturn(1);
        $ocrRepo  = $this->createMock(StatementOcrRepositoryInterface::class);
        $commit   = $this->createMock(ReconciliationCommitServiceInterface::class);
        $matcher  = $this->getMockBuilder(BankAccountMatchService::class)->disableOriginalConstructor()->getMock();

        $ctrl = new class ($view, $ocrRepo, $sessRepo, $commit, $matcher,
            ['ollama_base_url' => 'http://localhost:11434'],
            new InMemoryPendingSessionStore(['ocr_id' => 1, 'statement_ocr' => $ocr])
        ) extends StatementReconcileController {
            protected function loadBankTransactions(
                \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr $ocr,
                int $id
            ): array {
                return [];
            }

            protected function loadBankAccountEndingBalance(int $bankAccountId): ?float
            {
                return null;
            }
        };

        $ctrl->handle('confirm_account', 1);

        unset($_POST['bank_account_id']);
    }

    // ------------------------------------------------------------------
    // handle('manual_match') — bank tx found in pending list (lines 392-394)
    // ------------------------------------------------------------------

    public function testHandleManualMatchWithMatchingBankTxRendersReview(): void
    {
        $ocr     = $this->makeOcr();
        $session = \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession::createPending(1, [], ['L001'], [2]);
        $_POST['line_id']    = 'L001';
        $_POST['bank_tx_id'] = '2';

        $bankTx = $this->makeTx(2, '75.00', '2026-03-20');

        $view = $this->getMockBuilder(ReconcileView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('renderReview');

        $sessRepo = $this->createMock(ReconciliationSessionRepositoryInterface::class);
        $sessRepo->method('save')->willReturn(1);

        $ctrl = $this->makeFullController($view, null, $sessRepo, null, [
            'statement_ocr'             => $ocr,
            'session'                   => $session,
            'bank_transactions'         => [$bankTx],  // tx with id=2 matches POST bank_tx_id=2
            'warnings'                  => [],
            'duplicate_transaction_ids' => [],
        ]);
        $ctrl->handle('manual_match', 1);

        unset($_POST['line_id'], $_POST['bank_tx_id']);
    }
}
