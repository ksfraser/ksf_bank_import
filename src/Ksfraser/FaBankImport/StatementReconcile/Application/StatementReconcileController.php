<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\StatementOcrRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Service\ReconciliationCommitServiceInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClient;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClientInterface;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\PdfTextExtractor;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\StatementTextParser;
use Ksfraser\FaBankImport\StatementReconcile\Matching\SimpleMatchingEngine;

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
final class StatementReconcileController
{
    /** Session key used to persist a pending ReconciliationSession across requests. */
    private const SESSION_KEY = 'sr_pending_session';

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

    /** @var array Module config (from config.php). */
    private $config;

    /**
     * @param ReconcileView                            $view
     * @param StatementOcrRepositoryInterface          $ocrRepo
     * @param ReconciliationSessionRepositoryInterface $sessionRepo
     * @param ReconciliationCommitServiceInterface     $commitService
     * @param BankAccountMatchService                  $bankAccountMatchService
     * @param array                                    $config
     */
    public function __construct(
        ReconcileView $view,
        StatementOcrRepositoryInterface $ocrRepo,
        ReconciliationSessionRepositoryInterface $sessionRepo,
        ReconciliationCommitServiceInterface $commitService,
        BankAccountMatchService $bankAccountMatchService,
        array $config
    ) {
        $this->view                    = $view;
        $this->ocrRepo                 = $ocrRepo;
        $this->sessionRepo             = $sessionRepo;
        $this->commitService           = $commitService;
        $this->bankAccountMatchService = $bankAccountMatchService;
        $this->config                  = $config;
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

        // Build OCR pipeline.
        $pdfExtractor = new PdfTextExtractor();
        $ollama       = $this->buildOllamaClient();
        $parser       = new StatementTextParser(
            $pdfExtractor,
            $ollama,
            $this->config['ollama_ocr_model']       ?? 'glm-ocr',
            $this->config['ollama_extraction_model'] ?? 'gemma4'
        );

        // Step 1: Parse PDF → StatementOcr domain object.
        $statementOcr = $parser->parse($tmpPath);

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

        // Auto-match.
        $engine  = new SimpleMatchingEngine(
            (float) ($this->config['sr_match_threshold'] ?? SimpleMatchingEngine::DEFAULT_THRESHOLD)
        );
        $session = $engine->match($statementOcr, $bankTransactions);

        // Persist pending session.
        $sessionId = $this->sessionRepo->save($session);

        // Update PHP session with full review state.
        $this->storePendingSession([
            'ocr_id'            => $ocrId,
            'session_id'        => $sessionId,
            'bank_account_id'   => $bankAccountId,
            'statement_ocr'     => $statementOcr,
            'bank_transactions' => $bankTransactions,
            'session'           => $session,
        ]);

        $this->view->renderReview($statementOcr, $session, $bankTransactions);
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
        $closingBalance  = $statementOcr->getMetadata()->getClosingBalance();
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
            $pending['bank_transactions']
        );
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
    private function validateUpload(): array
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
    private function loadBankTransactions(StatementOcr $statementOcr, int $bankAccountId): array
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
    // Helpers: FA bank account list
    // -------------------------------------------------------------------------

    /**
     * Load all non-inactive FA bank accounts for the confirmation dropdown.
     *
     * @return array[] Each entry has 'id' and 'bank_account_name'.
     */
    private function loadAllFaBankAccounts(): array
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

    // -------------------------------------------------------------------------
    // Helpers: PHP session persistence for in-progress review
    // -------------------------------------------------------------------------

    /**
     * Store pending session data in $_SESSION.
     *
     * @param array $data
     */
    private function storePendingSession(array $data): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY] = $data;
    }

    /**
     * Load pending session data from $_SESSION, or null if none.
     *
     * @return array|null
     */
    private function loadPendingSession(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Remove the pending session from $_SESSION.
     */
    private function clearPendingSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
    }
}
