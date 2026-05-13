<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;
use PHPUnit\Framework\TestCase;

// Stub FA translation function so ReconcileView can be tested without FA bootstrap.
if (!function_exists('_')) {
    function _(string $str): string
    {
        return $str;
    }
}

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView
 */
class ReconcileViewTest extends TestCase
{
    /** Capture echo output from a callable. */
    private function capture(callable $fn): string
    {
        ob_start();
        $fn();
        return (string) ob_get_clean();
    }

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    private function makeOcr(array $lines = []): StatementOcr
    {
        $metadata = StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '1200.00',
            'account_identifier'   => '5678',
        ]);
        $raw = new RawOcrResult('{"model":"gemma4"}', 'gemma4');
        return StatementOcr::create($metadata, $lines, $raw);
    }

    private function makeLine(string $id = 'L001', string $amount = '99.99'): StatementLine
    {
        return new StatementLine(
            $id,
            new \DateTimeImmutable('2026-03-15'),
            'Amazon',
            $amount,
            'debit',
            ''
        );
    }

    private function makeBankTx(
        int    $id     = 1,
        string $amount = '99.99',
        string $date   = '2026-03-15'
    ): BankTransactionDto {
        return new BankTransactionDto(
            $id,
            new \DateTimeImmutable($date),
            $amount,
            'Amazon',
            'debit'
        );
    }

    private function makeSession(): ReconciliationSession
    {
        return ReconciliationSession::createPending(1, [], [], []);
    }

    // ------------------------------------------------------------------
    // renderUploadForm
    // ------------------------------------------------------------------

    public function testRenderUploadFormContainsFormAndFileInput(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderUploadForm());

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('pdf_file', $output);
        // action dispatched via hidden input (the form action attribute is "")
        $this->assertStringContainsString('name="action" value="parse"', $output);
        $this->assertStringContainsString('enctype="multipart/form-data"', $output);
    }

    public function testRenderUploadFormContainsSubmitButton(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderUploadForm());

        $this->assertStringContainsString('type="submit"', $output);
    }

    // ------------------------------------------------------------------
    // renderAccountConfirmation
    // ------------------------------------------------------------------

    public function testRenderAccountConfirmationContainsDropdown(): void
    {
        $view  = new ReconcileView();
        $accts = [
            ['id' => 1, 'bank_account_name' => 'RBC Chequing'],
            ['id' => 2, 'bank_account_name' => 'TD Savings'],
        ];
        $output = $this->capture(
            fn () => $view->renderAccountConfirmation([], null, '5678', $accts)
        );

        $this->assertStringContainsString('confirm_account', $output);
        $this->assertStringContainsString('bank_account_id', $output);
        $this->assertStringContainsString('RBC Chequing', $output);
        $this->assertStringContainsString('TD Savings', $output);
        $this->assertStringContainsString('5678', $output);
    }

    public function testRenderAccountConfirmationShowsBestMatchDetectedBanner(): void
    {
        $view  = new ReconcileView();
        $cands = [[
            'bank_account_id'     => 1,
            'bank_account_name'   => 'RBC Chequing',
            'bank_account_number' => '12345678',
            'bank_name'           => 'RBC',
            'score'               => 0.95,
            'match_method'        => 'exact_suffix',
        ]];
        $accts  = [['id' => 1, 'bank_account_name' => 'RBC Chequing']];
        $output = $this->capture(
            fn () => $view->renderAccountConfirmation($cands, 1, '5678', $accts)
        );

        $this->assertStringContainsString('Auto-detected account', $output);
        $this->assertStringContainsString('RBC Chequing', $output);
        $this->assertStringContainsString('95%', $output);
    }

    public function testRenderAccountConfirmationShowsWarningWhenNoBestMatch(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(
            fn () => $view->renderAccountConfirmation([], null, '0000', [])
        );

        $this->assertStringContainsString('Could not confidently', $output);
    }

    // ------------------------------------------------------------------
    // renderReview – core
    // ------------------------------------------------------------------

    public function testRenderReviewContainsBankTransactionTable(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine()]);
        $session = $this->makeSession();
        $txs     = [$this->makeBankTx()];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringContainsString('sr_bank_table', $output);
        $this->assertStringContainsString('FA Bank Transactions', $output);
        $this->assertStringContainsString('Amazon', $output);
        $this->assertStringContainsString('99.99', $output);
    }

    public function testRenderReviewContainsApproveForm(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, []));

        // action dispatched via hidden input (form action="" is blank)
        $this->assertStringContainsString('name="action" value="approve"', $output);
        $this->assertStringContainsString('sr_approve_form', $output);
    }

    public function testRenderReviewContainsBalanceSummary(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, []));

        $this->assertStringContainsString('500.00', $output);   // opening
        $this->assertStringContainsString('1,200.00', $output); // closing
    }

    public function testRenderReviewShowsMatchPercentage(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, [$this->makeBankTx()]));

        $this->assertStringContainsString('0%', $output); // 0/1 matched
    }

    // ------------------------------------------------------------------
    // REQ-013 / REQ-016 — Non-blocking warnings
    // ------------------------------------------------------------------

    /**
     * REQ-013: An opening-balance mismatch warning must appear as an amber banner.
     */
    public function testRenderReviewShowsWarningBannerWhenWarningsProvided(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview(
            $ocr,
            $session,
            [],
            [],
            ['Balance mismatch detected: opening balance 1000.00 vs FA 950.00']
        ));

        $this->assertStringContainsString('Balance mismatch detected', $output);
        $this->assertStringContainsString('Warning', $output);
        $this->assertStringContainsString('#fff8e1', $output); // amber background
    }

    /**
     * REQ-016: A sanity-check warning also appears.
     */
    public function testRenderReviewShowsStatementSanityWarning(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview(
            $ocr,
            $session,
            [],
            [],
            ['Statement sanity check: sum of lines 850.00 differs from expected change 700.00']
        ));

        $this->assertStringContainsString('Statement sanity check', $output);
    }

    /**
     * REQ-013/016: Multiple warnings all rendered.
     */
    public function testRenderReviewRendersMultipleWarningsSeparately(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview(
            $ocr,
            $session,
            [],
            [],
            ['Warning alpha', 'Warning beta']
        ));

        $this->assertStringContainsString('Warning alpha', $output);
        $this->assertStringContainsString('Warning beta', $output);
        $this->assertSame(2, substr_count($output, '#fff8e1'));
    }

    /**
     * No warning banner on clean path.
     */
    public function testRenderReviewNoWarningBannerWhenNoneProvided(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, []));

        $this->assertStringNotContainsString('#fff8e1', $output);
    }

    // ------------------------------------------------------------------
    // REQ-017 — Duplicate transaction highlighting
    // ------------------------------------------------------------------

    /**
     * REQ-017: Flagged duplicate bank rows must contain a "Duplicate" badge.
     */
    public function testRenderReviewMarksDuplicateTransactionWithBadge(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();
        $txs     = [
            $this->makeBankTx(1, '50.00', '2026-03-10'),
            $this->makeBankTx(2, '50.00', '2026-03-10'),
        ];

        $output = $this->capture(fn () => $view->renderReview(
            $ocr,
            $session,
            $txs,
            [],
            [],
            [1, 2]
        ));

        $this->assertStringContainsString('Duplicate', $output);
    }

    /**
     * REQ-017: No badge when no duplicates flagged.
     */
    public function testRenderReviewNoDuplicateBadgeWhenNoneFlagged(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, [$this->makeBankTx(1)]));

        $this->assertStringNotContainsString('Duplicate', $output);
    }

    /**
     * REQ-017: Only the flagged row has the badge.
     */
    public function testRenderReviewOnlyFlaggedRowHasDuplicateBadge(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();
        $txs     = [
            $this->makeBankTx(1, '50.00'),
            $this->makeBankTx(2, '75.00'),
        ];

        $output = $this->capture(fn () => $view->renderReview(
            $ocr,
            $session,
            $txs,
            [],
            [],
            [1]
        ));

        $this->assertSame(1, substr_count($output, 'Duplicate'));
    }

    // ------------------------------------------------------------------
    // REQ-015 — renderPrintSchedule
    // ------------------------------------------------------------------

    public function testRenderPrintScheduleContainsSectionHeaders(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [], 1));

        $this->assertStringContainsString('Bank Reconciliation Schedule', $output);
        $this->assertStringContainsString('Cleared Transactions', $output);
        $this->assertStringContainsString('Outstanding FA Transactions', $output);
        $this->assertStringContainsString('Unmatched Statement Lines', $output);
    }

    public function testRenderPrintScheduleShowsBalances(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [], 5));

        $this->assertStringContainsString('500.00', $output);
        $this->assertStringContainsString('1,200.00', $output);
    }

    public function testRenderPrintScheduleShowsPeriod(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [], 1));

        $this->assertStringContainsString('2026-03-01', $output);
        $this->assertStringContainsString('2026-03-31', $output);
    }

    public function testRenderPrintScheduleShowsPreparerUserId(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [], 42));

        $this->assertStringContainsString('42', $output);
        $this->assertStringContainsString('Prepared by', $output);
    }

    public function testRenderPrintScheduleListsClearedPairs(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $pair    = new MatchedPair('L001', 1, 0.95, ['EXACT_AMOUNT_DATE'], 41, 100);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, $txs, 1));

        $this->assertStringContainsString('41:100', $output); // FA ref
        $this->assertStringContainsString('95%', $output);
    }

    public function testRenderPrintScheduleShowsPrintButton(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [], 1));

        $this->assertStringContainsString('Print', $output);
        $this->assertStringContainsString('Back to Review', $output);
    }

    // ------------------------------------------------------------------
    // REQ-014 — Manual match form in renderReview
    // ------------------------------------------------------------------

    /**
     * REQ-014: Manual match form shown when unmatched lines and txs exist.
     */
    public function testRenderReviewShowsManualMatchFormWhenUnmatchedItemsExist(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $session = ReconciliationSession::createPending(1, [], ['L001'], [1]);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringContainsString('Manual Match', $output);
        $this->assertStringContainsString('name="action" value="manual_match"', $output);
        $this->assertStringContainsString('Match Selected', $output);
    }

    /**
     * REQ-014: Manual match form includes statement line selector.
     */
    public function testRenderReviewManualMatchFormContainsLineSelector(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $session = ReconciliationSession::createPending(1, [], ['L001'], [1]);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringContainsString('name="line_id"', $output);
        $this->assertStringContainsString('Amazon', $output); // line description in option
    }

    /**
     * REQ-014: Manual match form includes bank tx selector.
     */
    public function testRenderReviewManualMatchFormContainsBankTxSelector(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $session = ReconciliationSession::createPending(1, [], ['L001'], [1]);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringContainsString('name="bank_tx_id"', $output);
    }

    /**
     * REQ-014: No manual match form when everything is auto-matched.
     */
    public function testRenderReviewNoManualMatchFormWhenFullyMatched(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $pair    = new MatchedPair('L001', 1, 0.95, ['EXACT_AMOUNT_DATE']);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringNotContainsString('name="action" value="manual_match"', $output);
    }

    /**
     * REQ-014: "MANUAL" badge appears on manually matched pairs.
     */
    public function testRenderReviewShowsManualBadgeOnManualPairs(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $pair    = new MatchedPair('L001', 1, 1.0, ['MANUAL']);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringContainsString('MANUAL', $output);
    }

    // ------------------------------------------------------------------
    // renderSuccess
    // ------------------------------------------------------------------

    public function testRenderSuccessContainsSessionId(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderSuccess(42));

        $this->assertStringContainsString('42', $output);
        $this->assertStringContainsString('Reconciliation Committed', $output);
    }

    public function testRenderSuccessContainsLinkToStartOver(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderSuccess(1));

        $this->assertStringContainsString('Reconcile Another Statement', $output);
    }

    // ------------------------------------------------------------------
    // renderError
    // ------------------------------------------------------------------

    public function testRenderErrorContainsMessage(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderError('Something went wrong.'));

        $this->assertStringContainsString('Something went wrong.', $output);
        $this->assertStringContainsString('Error', $output);
    }

    public function testRenderErrorEscapesHtml(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderError('<script>alert(1)</script>'));

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testRenderErrorContainsBackLink(): void
    {
        $view   = new ReconcileView();
        $output = $this->capture(fn () => $view->renderError('Error'));

        $this->assertStringContainsString('Back to upload', $output);
    }

    // ------------------------------------------------------------------
    // Line 104: renderAccountConfirmation – history note when match_method
    //           contains the word 'history'.
    // ------------------------------------------------------------------

    public function testRenderAccountConfirmationShowsHistoryNote(): void
    {
        $view  = new ReconcileView();
        $cands = [[
            'bank_account_id'     => 1,
            'bank_account_name'   => 'RBC Chequing',
            'bank_account_number' => '12345678',
            'bank_name'           => 'RBC',
            'score'               => 0.80,
            'match_method'        => 'exact_suffix+history', // contains 'history'
        ]];
        $accts  = [['id' => 1, 'bank_account_name' => 'RBC Chequing']];
        $output = $this->capture(
            fn () => $view->renderAccountConfirmation($cands, 1, '5678', $accts)
        );

        $this->assertStringContainsString('Previously matched', $output);
    }

    // ------------------------------------------------------------------
    // Line 237: renderReview – due date cell rendered when set.
    // ------------------------------------------------------------------

    public function testRenderReviewShowsDueDateWhenSet(): void
    {
        $metadata = StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '1200.00',
            'account_identifier'   => '5678',
            'due_date'             => '2026-04-20',
        ]);
        $raw = new RawOcrResult('{}', 'gemma4');
        $ocr = StatementOcr::create($metadata, [], $raw);

        $view    = new ReconcileView();
        $session = $this->makeSession();
        $output  = $this->capture(fn () => $view->renderReview($ocr, $session, []));

        $this->assertStringContainsString('2026-04-20', $output);
        $this->assertStringContainsString('Due:', $output);
    }

    // ------------------------------------------------------------------
    // Line 329: renderReview – FA ref from getFaTransType() when non-null.
    // ------------------------------------------------------------------

    public function testRenderReviewShowsFaRefForTxWithFaType(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        $session = $this->makeSession();

        // BankTransactionDto with faTransType + faTransNo set.
        $tx = new BankTransactionDto(
            1,
            new \DateTimeImmutable('2026-03-15'),
            '99.99',
            'Amazon',
            'debit',
            41,   // faTransType
            200   // faTransNo
        );

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, [$tx]));

        $this->assertStringContainsString('41:200', $output);
    }

    // ------------------------------------------------------------------
    // Line 347: renderReview bank table – statement line label falls back to
    //           statementLineId when the line is absent from the OCR lineMap.
    // ------------------------------------------------------------------

    public function testRenderReviewBankTableUsesLineIdLabelWhenLineNotInMap(): void
    {
        $view    = new ReconcileView();
        // OCR has NO lines → lineMap is empty.
        $ocr     = $this->makeOcr([]);
        // A matched pair referencing 'GHOST-01' (not in OCR lines).
        $pair    = new MatchedPair('GHOST-01', 1, 0.95, ['EXACT_AMOUNT_DATE']);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        $this->assertStringContainsString('GHOST-01', $output);
    }

    // ------------------------------------------------------------------
    // Line 397: renderReview unmatched lines table – colspan row when line
    //           ID is not in the OCR lineMap.
    // ------------------------------------------------------------------

    public function testRenderReviewUnmatchedTableShowsLineIdWhenLineNotInMap(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([]); // no OCR lines
        // Session with unmatched line id 'GHOST-99' not present in OCR.
        $session = ReconciliationSession::createPending(1, [], ['GHOST-99'], []);

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, []));

        $this->assertStringContainsString('GHOST-99', $output);
    }

    // ------------------------------------------------------------------
    // Lines 405-415: biTxCrossRef rendering variants.
    // ------------------------------------------------------------------

    public function testRenderReviewBiTxCrossRefShowsUnprocessed(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $session = ReconciliationSession::createPending(1, [], ['L001'], []);

        $crossRef = ['L001' => ['status' => 0, 'link' => '']];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, [], $crossRef));

        $this->assertStringContainsString('Unprocessed', $output);
    }

    public function testRenderReviewBiTxCrossRefShowsUnprocessedWithLink(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $session = ReconciliationSession::createPending(1, [], ['L001'], []);

        $crossRef = ['L001' => ['status' => 0, 'link' => '/process.php?id=5']];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, [], $crossRef));

        $this->assertStringContainsString('Unprocessed', $output);
        $this->assertStringContainsString('Open in Process Statements', $output);
    }

    public function testRenderReviewBiTxCrossRefShowsProcessedWithLink(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        $session = ReconciliationSession::createPending(1, [], ['L001'], []);

        $crossRef = ['L001' => ['status' => 2, 'link' => '/view.php?id=5']];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, [], $crossRef));

        $this->assertStringContainsString('Processed', $output);
        $this->assertStringContainsString('View / Edit', $output);
    }

    // ------------------------------------------------------------------
    // Line 476: manual match line selector – label falls back to lineId
    //           when the line is not in the OCR lineMap.
    // ------------------------------------------------------------------

    public function testRenderReviewManualMatchLineOptionUsesLineIdWhenLineNotInMap(): void
    {
        $view    = new ReconcileView();
        // OCR has no lines → lineMap empty.
        $ocr     = $this->makeOcr([]);
        // Session with unmatched line 'GHOST-42' (not in OCR).
        $session = ReconciliationSession::createPending(1, [], ['GHOST-42'], [1]);
        $txs     = [$this->makeBankTx(1)];

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, $txs));

        // The option value/text should contain the raw lineId as fallback label.
        $this->assertStringContainsString('GHOST-42', $output);
    }

    // ------------------------------------------------------------------
    // Line 493: manual match bank tx selector – label falls back to '#id'
    //           when the tx is not in the bankMap.
    // ------------------------------------------------------------------

    public function testRenderReviewManualMatchBankTxOptionUsesBankTxIdWhenTxNotInMap(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001')]);
        // Session with unmatched FA tx id 9999 but bankTransactions array is empty.
        $session = ReconciliationSession::createPending(1, [], ['L001'], [9999]);

        $output = $this->capture(fn () => $view->renderReview($ocr, $session, []));

        $this->assertStringContainsString('#9999', $output);
    }

    // ------------------------------------------------------------------
    // Lines 680-720: renderPrintSchedule – Outstanding FA Transactions table.
    // ------------------------------------------------------------------

    public function testRenderPrintScheduleShowsOutstandingFaTxnsWhenPresent(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr();
        // Session with an unmatched FA bank tx id = 1.
        $session = ReconciliationSession::createPending(1, [], [], [1]);
        $txs     = [$this->makeBankTx(1, '50.00', '2026-03-10')];

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, $txs, 1));

        $this->assertStringContainsString('50.00', $output);
        $this->assertStringContainsString('2026-03-10', $output);
    }

    // ------------------------------------------------------------------
    // Lines 731-746: renderPrintSchedule – Unmatched Statement Lines table.
    // ------------------------------------------------------------------

    public function testRenderPrintScheduleShowsUnmatchedStatementLinesWhenPresent(): void
    {
        $view    = new ReconcileView();
        $ocr     = $this->makeOcr([$this->makeLine('L001', '77.77')]);
        // Session with an unmatched statement line id.
        $session = ReconciliationSession::createPending(1, [], ['L001'], []);

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [], 1));

        $this->assertStringContainsString('Amazon', $output);
        $this->assertStringContainsString('77.77', $output);
    }
    // csrfField() branch tests are in ReconcileViewCsrfTest.php (separate file with
    // braced namespace blocks so generate_csrf_token can be defined in global namespace).

    // ------------------------------------------------------------------
    // Lines 680-682: renderPrintSchedule – faRef fallback when pair has no
    //               FA type — uses bank tx FA type if available (line 681)
    //               or '#id' otherwise (line 682).
    // ------------------------------------------------------------------

    public function testRenderPrintScheduleClearedPairUsesTxFaRefWhenPairFaTypeIsNull(): void
    {
        $view = new ReconcileView();
        $ocr  = $this->makeOcr([$this->makeLine('L001')]);
        // Pair with NO FA type (getFaTransType() === null).
        $pair = new MatchedPair('L001', 1, 0.95, ['EXACT_AMOUNT_DATE']);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);
        // Tx WITH FA type 55 / transNo 200.
        $tx = new BankTransactionDto(
            1,
            new \DateTimeImmutable('2026-03-15'),
            '99.99',
            'Amazon',
            'debit',
            55,
            200
        );

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [$tx], 1));

        // The inner ternary true-branch: tx FA ref used (line 681).
        $this->assertStringContainsString('55:200', $output);
    }

    public function testRenderPrintScheduleClearedPairUsesBankTxIdWhenBothFaTypesNull(): void
    {
        $view = new ReconcileView();
        $ocr  = $this->makeOcr([$this->makeLine('L001')]);
        // Pair with NO FA type.
        $pair = new MatchedPair('L001', 1, 0.95, ['EXACT_AMOUNT_DATE']);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);
        // Tx also with NO FA type.
        $tx = $this->makeBankTx(1, '99.99');

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [$tx], 1));

        // The inner ternary false-branch: fallback to '#id' (line 682).
        $this->assertStringContainsString('#1', $output);
    }

    // ------------------------------------------------------------------
    // Line 713: renderPrintSchedule – outstanding FA tx uses FA type ref
    //           when tx has getFaTransType() !== null.
    // ------------------------------------------------------------------

    public function testRenderPrintScheduleOutstandingTxUsesFaTypeRefWhenAvailable(): void
    {
        $view = new ReconcileView();
        $ocr  = $this->makeOcr();
        // Session with unmatched FA bank tx id = 1.
        $session = ReconciliationSession::createPending(1, [], [], [1]);
        // Tx WITH FA type 55 / transNo 300.
        $tx = new BankTransactionDto(
            1,
            new \DateTimeImmutable('2026-03-10'),
            '50.00',
            'Hydro',
            'debit',
            55,
            300
        );

        $output = $this->capture(fn () => $view->renderPrintSchedule($ocr, $session, [$tx], 1));

        // True-branch of line 713 ternary.
        $this->assertStringContainsString('55:300', $output);
    }
}
