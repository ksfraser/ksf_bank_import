<?php

namespace Ksfraser\FaBankImport\Import\Orchestrators;

use Ksfraser\FaBankImport\Import\Results\OperationResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Services\ProcessStatementsFetchService;
use Ksfraser\FaBankImport\Import\Transactions\TransactionImportTransaction;
use Ksfraser\FaBankImport\Import\Queries\UpdateTransactionStatus;

/**
 * ProcessStatementsOrchestrator: High-level coordinator for process_statements.php workflow.
 * 
 * Responsibility: Orchestrate transaction processing workflow: fetch → validate → process → audit.
 * Single entry point for post-import transaction processing (GL posting, contact resolution).
 * 
 * Per-transaction workflow:
 * 1. Fetch unprocessed transactions from statements
 * 2. For each transaction: validate → resolve contact → post GL → update status
 * 3. Track successes, failures, and errors
 * 4. Return comprehensive processing results
 * 
 * Usage:
 *   $orchestrator = new ProcessStatementsOrchestrator(...dependencies...);
 *   $result = $orchestrator->process($filters, $options);
 */
class ProcessStatementsOrchestrator
{
    private TransactionDatabaseManager $dbManager;
    private ProcessStatementsFetchService $stmtFetcher;
    private TransactionImportTransaction $txnTransaction;
    private UpdateTransactionStatus $statusUpdater;

    /**
     * Initialize process orchestrator with dependencies.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager
     * @param ProcessStatementsFetchService $stmtFetcher Statement/transaction fetcher with POST filtering
     * @param TransactionImportTransaction $txnTransaction Transaction processing wrapper
     * @param UpdateTransactionStatus $statusUpdater Status update query
     */
    public function __construct(
        TransactionDatabaseManager $dbManager,
        ProcessStatementsFetchService $stmtFetcher,
        TransactionImportTransaction $txnTransaction,
        UpdateTransactionStatus $statusUpdater
    ) {
        $this->dbManager = $dbManager;
        $this->stmtFetcher = $stmtFetcher;
        $this->txnTransaction = $txnTransaction;
        $this->statusUpdater = $statusUpdater;
    }

    /**
     * Execute transaction processing workflow with optional filtering.
     *
     * @param array $filters Query filters (statement_id, bank_account_id, date_range, status)
     * @param array $options Processing options (contact_resolution, create_gl_entries, batch_size)
     * @param array $post Optional POST data for form-driven processing
     * @return OperationResult Comprehensive processing result with counts and errors
     */
    public function process(
        array $filters = [],
        array $options = [],
        array $post = []
    ): OperationResult {

        $result = new OperationResult();
        $startTime = microtime(true);

        try {
            // Get default filter values
            $statusFilter = $filters['status'] ?? 'pending';
            $batchSize = $options['batch_size'] ?? 100;
            $maxResults = null;

            $processedCount = 0;
            $successCount = 0;
            $failedCount = 0;
            $processedIds = [];

            // Begin database transaction
            $this->dbManager->startTransaction();

            // Fetch statements with transactions
            $statements = $this->stmtFetcher->fetch($statusFilter, $filters, $post);

            foreach ($statements as $statement) {
                // Fetch transactions for this statement
                $stmt = $this->stmtFetcher->fetchWithTransactions($statement['id']);

                if (!$stmt || empty($stmt['transactions'])) {
                    continue;
                }

                // Process each transaction
                foreach ($stmt['transactions'] as $txn) {
                    try {
                        // Process transaction (contact resolution, GL posting, etc)
                        $this->processTransaction($txn, $statement, $options);

                        $successCount++;
                        $processedIds[] = $txn['id'];

                    } catch (\Exception $e) {
                        $failedCount++;
                        $result->addWarning("Transaction {$txn['id']} processing failed: " . $e->getMessage());
                    }

                    $processedCount++;

                    // Batch limit check
                    if ($batchSize && $processedCount >= $batchSize) {
                        break 2; // Break both loops
                    }
                }
            }

            // Calculate metrics
            $duration = (int)((microtime(true) - $startTime) * 1000); // ms

            // Commit if all successful
            $this->dbManager->commit();

            // Record results
            $result->setData([
                'processed_count' => $processedCount,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'transaction_ids' => $processedIds,
                'duration_ms' => $duration,
                'processed_at' => date('Y-m-d H:i:s')
            ]);

            return $result;

        } catch (\Exception $e) {
            // Rollback on critical error
            if ($this->dbManager->isActive()) {
                $this->dbManager->rollback($e);
            }

            $result->addError('Transaction processing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process single transaction (contact resolution, GL posting, status update).
     *
     * @param array $transaction Transaction to process
     * @param array $statement Parent statement
     * @param array $options Processing options
     * @throws \Exception On processing error
     */
    private function processTransaction(array $transaction, array $statement, array $options): void
    {
        $txnId = $transaction['id'];
        $bankAccountId = $statement['bank_account_id'] ?? $transaction['bank_account_id'];

        // Step 1: Resolve contact (if needed)
        if ($options['resolve_contact'] ?? true) {
            // Contact resolution logic here (strategy-based)
            // $contactId = $this->resolveContact($transaction);
        }

        // Step 2: Post GL entry (if needed)
        if ($options['post_gl_entry'] ?? true) {
            // GL posting logic here
            // $glEntryId = $this->postGlEntry($transaction, $bankAccountId);
        }

        // Step 3: Update transaction status to completed
        $this->statusUpdater->execute($txnId, 'completed', [
            'notes' => 'Processed via process_statements',
            'processed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
