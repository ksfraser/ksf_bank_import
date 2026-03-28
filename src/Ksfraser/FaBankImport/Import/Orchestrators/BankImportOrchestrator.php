<?php

namespace Ksfraser\FaBankImport\Import\Orchestrators;

use Ksfraser\FaBankImport\Import\Results\StatementImportResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Services\TransactionFetchService;
use Ksfraser\FaBankImport\Import\Transactions\StatementImportTransaction;
use Ksfraser\FaBankImport\Import\Queries\MarkFileProcessed;

/**
 * BankImportOrchestrator: High-level coordinator for bank import_statements.php workflow.
 * 
 * Responsibility: Orchestrate complete import process: file loading → statement import → file marking.
 * Single entry point for bank statement import with comprehensive error handling.
 * 
 * Per-file workflow:
 * 1. Load statements from file
 * 2. For each statement: validate → import transactions → record results
 * 3. Mark file as processed with metadata (counts, totals, duration)
 * 4. Return comprehensive import results with transaction IDs, failures, errors
 * 
 * Usage:
 *   $orchestrator = new BankImportOrchestrator(...dependencies...);
 *   $result = $orchestrator->import($fileData, $bankAccountId, $options);
 */
class BankImportOrchestrator
{
    private TransactionDatabaseManager $dbManager;
    private StatementImportTransaction $stmtTransaction;
    private MarkFileProcessed $fileMarker;
    private TransactionFetchService $txnFetcher;

    /**
     * Initialize import orchestrator with dependencies.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager
     * @param StatementImportTransaction $stmtTransaction Statement import transaction wrapper
     * @param MarkFileProcessed $fileMarker File status updater
     * @param TransactionFetchService $txnFetcher Transaction fetcher for verification
     */
    public function __construct(
        TransactionDatabaseManager $dbManager,
        StatementImportTransaction $stmtTransaction,
        MarkFileProcessed $fileMarker,
        TransactionFetchService $txnFetcher
    ) {
        $this->dbManager = $dbManager;
        $this->stmtTransaction = $stmtTransaction;
        $this->fileMarker = $fileMarker;
        $this->txnFetcher = $txnFetcher;
    }

    /**
     * Execute complete bank import workflow for file.
     *
     * @param array $fileData File data with statements array
     * @param int $bankAccountId Bank account ID for import
     * @param array $options Processing options (check_duplicates, create_gl_entries, file_hash)
     * @return array Array of StatementImportResult objects with comprehensive metrics
     */
    public function import(
        array $fileData,
        int $bankAccountId,
        array $options = []
    ): array {

        $startTime = microtime(true);
        $results = [];
        $totalImported = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        try {
            // Begin database transaction for entire import
            $this->dbManager->startTransaction();

            // Import each statement in file
            $statements = $fileData['statements'] ?? [];

            foreach ($statements as $stmtData) {
                try {
                    $result = $this->stmtTransaction->execute($stmtData, $bankAccountId, $options);

                    $totalImported += $result->getImportedCount();
                    $totalFailed += $result->getFailedCount();
                    $totalSkipped += $result->getSkippedCount();

                    $results[] = $result;

                } catch (\Exception $e) {
                    // Log error but continue with other statements
                    $errorResult = new StatementImportResult();
                    $errorResult->addError('Statement import failed: ' . (string)$e);
                    $results[] = $errorResult;
                    $totalFailed++;
                }
            }

            // Calculate metrics
            $duration = (int)((microtime(true) - $startTime) * 1000); // ms

            // Mark file as processed
            $this->fileMarker->execute(
                $fileData['id'] ?? 0,
                'processed',
                [
                    'import_count' => $totalImported,
                    'error_count' => $totalFailed,
                    'duration' => $duration,
                    'file_hash' => $options['file_hash'] ?? null
                ]
            );

            // Commit transaction
            $this->dbManager->commit();

            return $results;

        } catch (\Exception $e) {
            // Rollback entire import on critical error
            if ($this->dbManager->isActive()) {
                $this->dbManager->rollback($e);
            }

            // Mark file as failed
            try {
                $this->fileMarker->execute(
                    $fileData['id'] ?? 0,
                    'failed',
                    [
                        'error_message' => $e->getMessage(),
                        'duration' => (int)((microtime(true) - $startTime) * 1000)
                    ]
                );
            } catch (\Exception $fileMarkError) {
                // Can't mark file - log but continue
            }

            throw $e;
        }
    }
}
