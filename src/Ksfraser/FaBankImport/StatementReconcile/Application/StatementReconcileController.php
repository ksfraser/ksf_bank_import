<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\StatementOcrRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Application\PendingSessionStoreInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Service\ReconciliationCommitServiceInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClient;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClientInterface;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\PdfTextExtractor;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\StatementTextParser;
use Ksfraser\FaBankImport\StatementReconcile\Matching\SimpleMatchingEngine;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Application-layer controller for the PDF CC statement reconciliation workflow.
 *
 * Handles three actions dispatched from reconcile_statement.php:
 *
 *   (default)  Show the upload form.
 *   parse      Accept PDF upload → OCR → auto-match → store pending session in $_SESSION.
 *   approve    Commit approved session to FA and mark it in the DB.
 *   remove     Remove a matched pair from the pending session (AJAX-friendly).
 *
 * The controller is intentionally thin: it orchestrates domain services and
 * delegates all rendering to ReconcileView.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Application
 * @author  Kevin Fraser
 */
class StatementReconcileController
{
    /** Maximum size (bytes) of an uploaded PDF. 10 MB. */
    private const MAX_PDF_BYTES = 10 * 1024 * 1024;

    /** @var ReconcileView */
    private $view;

    /** @var StatementOcrRepositoryInterface */
    private $ocrRepo;

    /** @var ReconciliationSessionRepositoryInterface */
    private $sessionRepo;

    /** @var ReconciliationCommitServiceInterface */
    private $commitService;

    /** @var BankAccountMatchService */
    private $bankAccountMatchService;

    /** @var PendingSessionStoreInterface */
    private $pendingStore;

    /** @var array Module config (from config.php). */
    private $config;

    /**
     * @param ReconcileView                            $view
     * @param StatementOcrRepositoryInterface          $ocrRepo
     * @param ReconciliationSessionRepositoryInterface $sessionRepo
     * @param ReconciliationCommitServiceInterface     $commitService
     * @param BankAccountMatchService                  $bankAccountMatchService
     * @param array                                    $config
     * @param PendingSessionStoreInterface|null        $pendingStore  Defaults to PhpSessionPendingSessionStore.
     */
    public function __construct(
        ReconcileView $view,
        StatementOcrRepositoryInterface $ocrRepo,
        ReconciliationSessionRepositoryInterface $sessionRepo,
        ReconciliationCommitServiceInterface $commitService,
        BankAccountMatchService $bankAccountMatchService,
        array $config,
        ?PendingSessionStoreInterface $pendingStore = null
    ) {
        $this->view                    = $view;
        $this->ocrRepo                 = $ocrRepo;
        $this->sessionRepo             = $sessionRepo;
        $this->commitService           = $commitService;
        $this->bankAccountMatchService = $bankAccountMatchService;
        $this->config                  = $config;
        $this->pendingStore            = $pendingStore ?? new PhpSessionPendingSessionStore();
    }

    /**
     * Main dispatcher.  Call from reconcile_statement.php after FA bootstrap.
     *
     * @param string $action Value of $_POST['action'] or empty string.
     * @param int    $userId FA user ID of the logged-in user.
     */
    public function handle(string $action, int $userId): void
    {
        try {
            switch ($action) {
                case 'parse':
                    $this->handleParse();
                    break;

                case 'confirm_account':
                    $this->handleConfirmAccount($userId);
                    break;

                case 'approve':
                    $this->handleApprove($userId);
                    break;

                case 'remove_pair':
                    $this->handleRemovePair();
                    break;

                case 'manual_match':
                    $this->handleManualMatch($userId);
                    break;

                case 'print_schedule':
                    $this->handlePrintSchedule($userId);
                    break;

                default:
                    $this->clearPendingSession();
                    $this->view->renderUploadForm();
                    break;
            }
        } catch (StatementOcrException $e) {
            $this->view->renderError('OCR / parsing error: ' . $e->getMessage());
        } catch (ReconciliationException $e) {
            $this->view->renderError('Reconciliation error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->view->renderError('Unexpected error: ' . htmlspecialchars($e->getMessage()));
        }
    }

    // -------------------------------------------------------------------------
    // Action: parse – upload → OCR → bank account detection → confirmation screen
    // -------------------------------------------------------------------------

    /**
     * Process the uploaded PDF: OCR, auto-detect bank account, show confirmation screen.
     */
    private function handleParse(): void
    {
        $upload  = $this->validateUpload();
        $tmpPath = $upload['tmp_name'];

        // Step 1: Parse PDF → StatementOcr domain object (OCR pipeline encapsulated in buildStatementOcr).
        $statementOcr = $this->buildStatementOcr($tmpPath);

        // Step 2: Persist the OCR result so it has an ID for FK references.
        $ocrId = $this->ocrRepo->save($statementOcr);

        // Step 3: Auto-detect FA bank account from OCR account identifier.
        $accountIdentifier = (string) ($statementOcr->getMetadata()->getAccountIdentifier() ?? '');
        $matchResult       = $this->bankAccountMatchService->match($accountIdentifier);

        // Step 4: Load all active FA bank accounts for the confirmation dropdown.
        $allBankAccounts = $this->loadAllFaBankAccounts();

        // Step 5: Store partial state in PHP session (no bank_account_id yet).
        $this->storePendingSession([
            'ocr_id'             => $ocrId,
            'statement_ocr'      => $statementOcr,
            'match_results'      => $matchResult['candidates'],
            'best_id'            => $matchResult['best_id'],
            'account_identifier' => $accountIdentifier,
        ]);

        // Step 6: Show account confirmation screen.
        $this->view->renderAccountConfirmation(
            $matchResult['candidates'],
            $matchResult['best_id'],
            $accountIdentifier,
            $allBankAccounts
        );
    }

    // -------------------------------------------------------------------------
    // Action: confirm_account – user confirms FA bank account, run matching
    // -------------------------------------------------------------------------

    /**
     * Receive the confirmed bank account, load FA transactions, run matching, show review.
     *
     * @param int $userId
     */
    private function handleConfirmAccount(int $userId): void
    {
        $pending = $this->loadPendingSession();
        if ($pending === null) {
            $this->view->renderError('Session expired. Please re-upload the PDF.');
            return;
        }

        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
        if ($bankAccountId <= 0) {
            $this->view->renderError('Please select a bank account.');
            return;
        }

        /** @var StatementOcr $statementOcr */
        $statementOcr = $pending['statement_ocr'];
        $ocrId        = (int) ($pending['ocr_id'] ?? 0);

        // Load FA native bank transactions for matching.
        $bankTransactions = $this->loadBankTransactions($statementOcr, $bankAccountId);

        // REQ-017: detect possible duplicate bank transactions (same date + amount).
        $duplicateTransactionIds = $this->detectDuplicateTransactionIds($bankTransactions);

        // REQ-013: compare OCR opening balance to FA ending_reconcile_balance (non-blocking).
        $warnings = [];
        $faEndingBalance = $this->loadBankAccountEndingBalance($bankAccountId);
        $ocrOpeningBalance = (float) $statementOcr->getMetadata()->getOpeningBalance();
        if ($faEndingBalance !== null && abs($faEndingBalance - $ocrOpeningBalance) > 0.01) {
            $warnings[] = sprintf(
                _('Balance mismatch (REQ-013): statement opening balance is %s but '
                    . 'the FA last ending reconcile balance for this account is %s. '
                    . 'This may indicate a gap in the reconciliation history.'),
                number_format($ocrOpeningBalance, 2),
                number_format($faEndingBalance, 2)
            );
        }

        // REQ-016: sum OCR line amounts vs |closing - opening| sanity check (non-blocking).
        $lineTotal = 0.0;
        foreach ($statementOcr->getLines() as $line) {
            $lineTotal += (float) $line->getAmount();
        }
        $expectedDiff = abs((float) $statementOcr->getMetadata()->getClosingBalance() - $ocrOpeningBalance);
        if ($expectedDiff > 0.0 && abs($lineTotal - $expectedDiff) > 0.01) {
            $warnings[] = sprintf(
                _('Statement sanity check (REQ-016): sum of OCR line amounts (%s) differs '
                    . 'from the expected balance change (%s). '
                    . 'The statement may be incomplete or contain excluded items.'),
                number_format($lineTotal, 2),
                number_format($expectedDiff, 2)
            );
        }

        // Auto-match.
        $engine  = new SimpleMatchingEngine(
            (float) ($this->config['sr_match_threshold'] ?? SimpleMatchingEngine::DEFAULT_THRESHOLD)
        );
        $session = $engine->match($statementOcr, $bankTransactions);

        // Persist pending session.
        $sessionId = $this->sessionRepo->save($session);

        // Update PHP session with full review state.
        $this->storePendingSession([
            'ocr_id'                   => $ocrId,
            'session_id'               => $sessionId,
            'bank_account_id'          => $bankAccountId,
            'statement_ocr'            => $statementOcr,
            'bank_transactions'        => $bankTransactions,
            'session'                  => $session,
            'warnings'                 => $warnings,
            'duplicate_transaction_ids'=> $duplicateTransactionIds,
        ]);

        $this->view->renderReview(
            $statementOcr,
            $session,
            $bankTransactions,
            [],
            $warnings,
            $duplicateTransactionIds
        );
    }

    // -------------------------------------------------------------------------
    // Action: approve
    // -------------------------------------------------------------------------

    /**
     * Commit the approved session to FA and show success.
     *
     * @param int $userId
     */
    private function handleApprove(int $userId): void
    {
        $pending = $this->loadPendingSession();
        if ($pending === null) {
            $this->view->renderError('No pending reconciliation session found. Please start over.');
            return;
        }

        $sessionId     = (int) ($pending['session_id'] ?? 0);
        $bankAccountId = (int) ($pending['bank_account_id'] ?? 0);

        if ($sessionId <= 0) {
            $this->view->renderError('Invalid session ID. Please start over.');
            return;
        }

        /** @var \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr $statementOcr */
        $statementOcr    = $pending['statement_ocr'];
        $closingBalance  = (float) $statementOcr->getMetadata()->getClosingBalance();
        $statementEndDate = $statementOcr->getMetadata()->getStatementEndDate()->format('Y-m-d');

        $this->commitService->commit($sessionId, $userId, $bankAccountId, $statementEndDate, $closingBalance);
        $this->clearPendingSession();

        $this->view->renderSuccess($sessionId);
    }

    // -------------------------------------------------------------------------
    // Action: remove_pair
    // -------------------------------------------------------------------------

    /**
     * Remove a matched pair from the pending session (user adjustment).
     * Re-renders the review screen.
     */
    private function handleRemovePair(): void
    {
        $lineId = trim((string) ($_POST['line_id'] ?? ''));
        if ($lineId === '') {
            $this->view->renderError('Missing line_id for remove_pair action.');
            return;
        }

        $pending = $this->loadPendingSession();
        if ($pending === null) {
            $this->view->renderError('Session expired. Please re-upload the PDF.');
            return;
        }

        /** @var ReconciliationSession $session */
        $session = $pending['session'];
        $session->removePair($lineId);

        // Re-persist updated session.
        $this->sessionRepo->save($session);
        $pending['session'] = $session;
        $this->storePendingSession($pending);

        $this->view->renderReview(
            $pending['statement_ocr'],
            $session,
            $pending['bank_transactions'],
            [],
            $pending['warnings'] ?? [],
            $pending['duplicate_transaction_ids'] ?? []
        );
    }

    // -------------------------------------------------------------------------
    // Action: manual_match
    // -------------------------------------------------------------------------

    /**
     * Apply a manually selected line↔bank_tx pair to the pending session (REQ-014).
     *
     * @param int $userId
     */
    private function handleManualMatch(int $userId): void
    {
        $lineId   = trim((string) ($_POST['line_id']   ?? ''));
        $bankTxId = (int)          ($_POST['bank_tx_id'] ?? 0);

        if ($lineId === '') {
            $this->view->renderError('Missing line_id for manual_match action.');
            return;
        }
        if ($bankTxId <= 0) {
            $this->view->renderError('Missing or invalid bank_tx_id for manual_match action.');
            return;
        }

        $pending = $this->loadPendingSession();
        if ($pending === null) {
            $this->view->renderError('Session expired. Please re-upload the PDF.');
            return;
        }

        /** @var ReconciliationSession $session */
        $session = $pending['session'];

        // Look up the bank transaction FA keys so they survive commit.
        $bankTransactions = $pending['bank_transactions'] ?? [];
        $bankTx           = null;
        foreach ($bankTransactions as $tx) {
            if ($tx->getId() === $bankTxId) {
                $bankTx = $tx;
                break;
            }
        }

        $pair = new MatchedPair(
            $lineId,
            $bankTxId,
            1.0,
            ['MANUAL'],
            $bankTx !== null ? $bankTx->getFaTransType() : null,
            $bankTx !== null ? $bankTx->getFaTransNo()   : null
        );

        $session->addPair($pair);

        // Re-persist and re-render.
        $this->sessionRepo->save($session);
        $pending['session'] = $session;
        $this->storePendingSession($pending);

        $this->view->renderReview(
            $pending['statement_ocr'],
            $session,
            $bankTransactions,
            [],
            $pending['warnings'] ?? [],
            $pending['duplicate_transaction_ids'] ?? []
        );
    }

    // -------------------------------------------------------------------------
    // Action: print_schedule
    // -------------------------------------------------------------------------

    /**
     * Render the printable reconciliation schedule (REQ-015).
     *
     * @param int $userId
     */
    private function handlePrintSchedule(int $userId): void
    {
        $pending = $this->loadPendingSession();
        if ($pending === null) {
            $this->view->renderError('No pending reconciliation session found. Please start over.');
            return;
        }

        $this->view->renderPrintSchedule(
            $pending['statement_ocr'],
            $pending['session'],
            $pending['bank_transactions'] ?? [],
            $userId
        );
    }

    // -------------------------------------------------------------------------
    // Protected helpers: OCR pipeline factory (overridable in tests)
    // -------------------------------------------------------------------------

    /**
     * Build the OCR pipeline, parse the PDF, and return the StatementOcr aggregate.
     *
     * Extracted as a protected method so test subclasses can return a fixture
     * StatementOcr without requiring a real PDF file or Ollama.
     *
     * @param string $tmpPath Absolute path to the uploaded PDF.
     * @return StatementOcr
     */
    protected function buildStatementOcr(string $tmpPath): StatementOcr
    {
        $pdfExtractor = new PdfTextExtractor(new PdfParser());
        $ollama       = $this->buildOllamaClient();
        $parser       = new StatementTextParser(
            $pdfExtractor,
            $ollama,
            $this->config['ollama_ocr_model']       ?? 'glm-ocr',
            $this->config['ollama_extraction_model'] ?? 'gemma4'
        );
        return $parser->parse($tmpPath);
    }

    // -------------------------------------------------------------------------
    // Helpers: upload validation
    // -------------------------------------------------------------------------

    /**
     * Validate the PDF upload and return the $_FILES entry.
     *
     * @return array{name:string,tmp_name:string,size:int}
     * @throws \RuntimeException on invalid upload.
     */
    protected function validateUpload(): array
    {
        if (empty($_FILES['pdf_file']['tmp_name'])
            || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK
        ) {
            $code = isset($_FILES['pdf_file']['error'])
                ? (int) $_FILES['pdf_file']['error']
                : -1;
            throw new \RuntimeException(
                'File upload failed (error code ' . $code . '). '
                . 'Please check the file and try again.'
            );
        }

        $size = (int) ($_FILES['pdf_file']['size'] ?? 0);
        if ($size > self::MAX_PDF_BYTES) {
            throw new \RuntimeException(
                'Uploaded file exceeds the 10 MB limit ('
                . round($size / 1048576, 1) . ' MB).'
            );
        }

        // Validate MIME type via finfo – do not trust $_FILES['type'].
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['pdf_file']['tmp_name']);
        if ($mime !== 'application/pdf') {
            throw new \RuntimeException(
                'Uploaded file does not appear to be a PDF (detected: ' . $mime . ').'
            );
        }

        return [
            'name'     => basename((string) ($_FILES['pdf_file']['name'] ?? 'upload.pdf')),
            'tmp_name' => (string) $_FILES['pdf_file']['tmp_name'],
            'size'     => $size,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers: bank transaction loading
    // -------------------------------------------------------------------------

    /**
     * Load unreconciled FA native bank transactions for the given bank account and
     * the statement's date range.
     *
     * Queries `0_bank_trans` (FA's authoritative bank transaction table) rather
     * than `bi_transactions` (which is only the module's staging/import table).
     *
     * Unreconciled rows: `reconciled = '0000-00-00'` (FA convention for not-yet-reconciled).
     *
     * @param StatementOcr $statementOcr
     * @param int          $bankAccountId  FA 0_bank_accounts.id
     * @return BankTransactionDto[]
     */
    protected function loadBankTransactions(StatementOcr $statementOcr, int $bankAccountId): array
    {
        $meta  = $statementOcr->getMetadata();
        $start = $meta->getStatementStartDate()->format('Y-m-d');
        $end   = $meta->getStatementEndDate()->format('Y-m-d');

        // Query FA's native bank_trans table.
        // FA uses type+trans_no as composite PK.  We assign a local sequence `id`
        // so the matching engine can use an integer key internally.
        $sql = "
            SELECT t.type            AS fa_trans_type,
                   t.trans_no        AS fa_trans_no,
                   t.ref             AS description,
                   t.trans_date      AS date,
                   ABS(t.amount)     AS amount,
                   CASE WHEN t.amount >= 0 THEN 'debit' ELSE 'credit' END AS type
              FROM " . TB_PREF . "bank_trans t
             WHERE t.bank_act = " . db_escape($bankAccountId) . "
               AND t.trans_date <= " . db_escape($end) . "
               AND (t.reconciled = '0000-00-00' OR t.reconciled IS NULL)
             ORDER BY t.trans_date ASC, t.type ASC, t.trans_no ASC
        ";

        $result = db_query($sql, 'StatementReconcile: could not fetch FA bank transactions');

        $transactions = [];
        $sequence     = 0;

        while ($row = db_fetch($result)) {
            $sequence++;
            try {
                $transactions[] = BankTransactionDto::fromArray([
                    'id'           => $sequence,
                    'date'         => (string) $row['date'],
                    'amount'       => number_format((float) $row['amount'], 2, '.', ''),
                    'description'  => (string) ($row['description'] ?? ''),
                    'type'         => (string) ($row['type'] ?? 'debit'),
                    'fa_trans_type'=> (int) $row['fa_trans_type'],
                    'fa_trans_no'  => (int) $row['fa_trans_no'],
                ]);
            } catch (\InvalidArgumentException $e) {
                error_log(
                    'StatementReconcile: skipping malformed 0_bank_trans row '
                    . 'type=' . ($row['fa_trans_type'] ?? '?')
                    . ' trans_no=' . ($row['fa_trans_no'] ?? '?')
                    . ': ' . $e->getMessage()
                );
            }
        }

        return $transactions;
    }

    // -------------------------------------------------------------------------
    // Helpers: FA bank account – ending reconcile balance (REQ-013)
    // -------------------------------------------------------------------------

    /**
     * Query the last ending reconcile balance for a FA bank account.
     *
     * Returns null when FA globals are unavailable (unit-test environment) or the
     * account record has no prior reconciliation data.
     *
     * @param int $bankAccountId
     * @return float|null
     */
    protected function loadBankAccountEndingBalance(int $bankAccountId): ?float
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT ending_reconcile_balance
                  FROM " . TB_PREF . "bank_accounts
                 WHERE id = " . db_escape($bankAccountId);

        $result = db_query($sql, 'StatementReconcile: could not fetch bank account ending balance');
        $row    = db_fetch($result);
        if (!$row || $row['ending_reconcile_balance'] === null) {
            return null;
        }
        return (float) $row['ending_reconcile_balance'];
    }

    // -------------------------------------------------------------------------
    // Helpers: duplicate FA bank transaction detection (REQ-017)
    // -------------------------------------------------------------------------

    /**
     * Detect FA bank transactions with the same date + amount.
     *
     * Returns the sequence IDs (integer keys assigned during load) of any rows
     * that appear to be duplicates.  Both members of a duplicate pair are included
     * so the view can highlight all of them.
     *
     * @param BankTransactionDto[] $bankTransactions
     * @return int[]
     */
    protected function detectDuplicateTransactionIds(array $bankTransactions): array
    {
        // Group by date|amount key.
        $groups = [];
        foreach ($bankTransactions as $tx) {
            $key = $tx->getDate()->format('Y-m-d') . '|' . $tx->getAmount();
            $groups[$key][] = $tx->getId();
        }

        $duplicateIds = [];
        foreach ($groups as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplicateIds[] = $id;
                }
            }
        }

        return $duplicateIds;
    }

    // -------------------------------------------------------------------------
    // Helpers: FA bank account list
    // -------------------------------------------------------------------------

    /**
     * Load all non-inactive FA bank accounts for the confirmation dropdown.
     *
     * @return array[] Each entry has 'id' and 'bank_account_name'.
     */
    protected function loadAllFaBankAccounts(): array
    {
        $sql    = "SELECT id, bank_account_name
                     FROM " . TB_PREF . "bank_accounts
                    WHERE inactive = 0
                    ORDER BY bank_account_name ASC";
        $result = db_query($sql, 'StatementReconcile: could not fetch FA bank accounts');
        $list   = [];
        while ($row = db_fetch($result)) {
            $list[] = $row;
        }
        return $list;
    }

    // -------------------------------------------------------------------------
    // Helpers: Ollama client factory
    // -------------------------------------------------------------------------

    /**
     * Build the Ollama client from module config.
     *
     * @return OllamaClientInterface
     */
    private function buildOllamaClient(): OllamaClientInterface
    {
        $baseUrl = trim($this->config['ollama_base_url'] ?? '');
        if ($baseUrl === '') {
            throw new \RuntimeException(
                'OLLAMA_BASE_URL is not configured. '
                . 'Add "ollama_base_url" to config.php.'
            );
        }

        $apiKey  = $this->config['ollama_api_key'] ?? null;
        $timeout = (int) ($this->config['ollama_timeout_ms'] ?? 30000);

        // OllamaClient accepts base URL, optional API key, and timeout in milliseconds.
        return new OllamaClient($baseUrl, $apiKey, $timeout);
    }

    private function storePendingSession(array $data): void
    {
        $this->pendingStore->store($data);
    }

    private function loadPendingSession(): ?array
    {
        return $this->pendingStore->load();
    }

    private function clearPendingSession(): void
    {
        $this->pendingStore->clear();
    }
}
