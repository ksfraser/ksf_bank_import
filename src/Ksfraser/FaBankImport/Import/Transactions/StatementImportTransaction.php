<?php

namespace Ksfraser\FaBankImport\Import\Transactions;

use Ksfraser\FaBankImport\Import\Results\StatementImportResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Queries\InsertBiStatement;
use Ksfraser\FaBankImport\Import\Queries\InsertBiTransaction;
use Ksfraser\FaBankImport\Import\Validators\ImportStatementValidator;
use Ksfraser\FaBankImport\Import\Validators\TransactionValidator;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;
use Ksfraser\FaBankImport\Import\Exceptions\StatementValidationException;
use Ksfraser\FaBankImport\Import\Exceptions\ImportException;

/**
 * StatementImportTransaction: Atomic wrapper for entire statement import workflow.
 * 
 * Responsibility: Execute complete statement import (validation → database insert → GL posting)
 * within a single SAVEPOINT transaction. Provides rollback on any critical error.
 * 
 * **CRITICAL FOR DATA INTEGRITY**: Ensures all-or-nothing semantics. If any transaction
 * fails (including same-account transfer detection), entire statement rollback occurs.
 * 
 * Usage:
 *   $txn = new StatementImportTransaction($dbManager);
 *   $result = $txn->execute($statementData, $bankAccountId, ['create_gl_entries' => true]);
 */
class StatementImportTransaction
{
    private TransactionDatabaseManager $dbManager;
    private InsertBiStatement $insertStatement;
    private InsertBiTransaction $insertTransaction;
    private ImportStatementValidator $statementValidator;
    private TransactionValidator $transactionValidator;

    /**
     * Initialize statement transaction wrapper with dependencies.
     *
     * @param TransactionDatabaseManager $dbManager Database manager with SAVEPOINT support
     * @param InsertBiStatement $insertStatement Statement insert query
     * @param InsertBiTransaction $insertTransaction Transaction insert query
     * @param ImportStatementValidator $statementValidator Statement validation rules
     * @param TransactionValidator $transactionValidator Transaction validation rules
     */
    public function __construct(
        TransactionDatabaseManager $dbManager,
        InsertBiStatement $insertStatement,
        InsertBiTransaction $insertTransaction,
        ImportStatementValidator $statementValidator,
        TransactionValidator $transactionValidator
    ) {
        $this->dbManager = $dbManager;
        $this->insertStatement = $insertStatement;
        $this->insertTransaction = $insertTransaction;
        $this->statementValidator = $statementValidator;
        $this->transactionValidator = $transactionValidator;
    }

    /**
     * Execute complete statement import with full rollback on any error.
     *
     * @param array $statementData Complete statement data with transactions array
     * @param int $bankAccountId Bank account ID for import
     * @param array $options Processing options (check_duplicates, create_gl_entries, etc)
     * @return StatementImportResult Import result with transaction IDs, counts, and errors
     * @throws TransactionProcessingException On critical unrecoverable errors
     */
    public function execute(
        array $statementData,
        int $bankAccountId,
        array $options = []
    ): StatementImportResult {

        $result = new StatementImportResult();
        $savepointName = 'sp_stmt_import_' . time() . '_' . rand(10000, 99999);

        try {
            // Create savepoint for entire statement import
            $this->dbManager->createSavepoint($savepointName);

            // Validate statement before any inserts
            $validationResult = $this->statementValidator->validate($statementData);
            if (!$validationResult->isValid()) {
                $this->dbManager->rollbackToSavepoint($savepointName);
                throw new StatementValidationException(
                    'Statement validation failed',
                    context: ['errors' => $validationResult->getErrors()]
                );
            }

            // Insert statement record
            $stmtResult = $this->insertStatement->execute($statementData, $bankAccountId, $options);
            $statementId = $stmtResult->getStatementId();
            $result->setStatementId($statementId);

            // Import transactions one by one
            $transactions = $statementData['transactions'] ?? [];
            $importedCount = 0;
            $failedCount = 0;
            $skippedCount = 0;
            $transactionIds = [];

            foreach ($transactions as $txnData) {
                try {
                    // Validate transaction
                    $txnValidationResult = $this->transactionValidator->validate(
                        $txnData,
                        $bankAccountId,
                        $options
                    );

                    if (!$txnValidationResult->isValid()) {
                        // Non-critical validation failures: log and skip
                        $result->addWarning(
                            "Transaction skipped: " . implode(', ', $txnValidationResult->getErrors())
                        );
                        $skippedCount++;
                        continue;
                    }

                    // Insert transaction
                    $txnResult = $this->insertTransaction->execute(
                        $txnData,
                        $statementId,
                        $bankAccountId,
                        $options
                    );

                    $transactionIds[] = $txnResult->getData()['transaction_id'];
                    $importedCount++;

                } catch (ImportException $e) {
                    // Critical error: stop processing and rollback entire statement
                    $this->dbManager->rollbackToSavepoint($savepointName);
                    $result->addError("Critical transaction error: " . (string)$e);
                    throw new TransactionProcessingException(
                        'Statement import rolled back due to critical transaction error',
                        context: [
                            'statement_id' => $statementId,
                            'imported_count' => $importedCount,
                            'error' => (string)$e
                        ]
                    );
                } catch (\Exception $e) {
                    // Non-critical error: log and continue
                    $failedCount++;
                    $result->addWarning("Transaction processing error: " . $e->getMessage());
                }
            }

            // Record import statistics
            $result->setImportedCount($importedCount);
            $result->setFailedCount($failedCount);
            $result->setSkippedCount($skippedCount);
            $result->setData([
                'statement_id' => $statementId,
                'transactions_imported' => $importedCount,
                'transactions_failed' => $failedCount,
                'transactions_skipped' => $skippedCount,
                'transaction_ids' => $transactionIds,
                'total_amount' => $stmtResult->getData()['total_amount'] ?? 0,
                'import_completed_at' => date('Y-m-d H:i:s')
            ]);

            return $result;

        } catch (TransactionProcessingException $e) {
            // Already handled - rollback done
            throw $e;
        } catch (\Exception $e) {
            // Rollback on unexpected errors
            if ($this->dbManager->isActive()) {
                $this->dbManager->rollbackToSavepoint($savepointName);
            }
            throw new TransactionProcessingException(
                'Unexpected error in statement import transaction: ' . $e->getMessage(),
                context: [
                    'bank_account_id' => $bankAccountId,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }
}
