<?php

namespace Ksfraser\FaBankImport\Import\Transactions;

use Ksfraser\FaBankImport\Import\Results\TransactionProcessResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Queries\InsertBiTransaction;
use Ksfraser\FaBankImport\Import\Queries\UpdateTransactionStatus;
use Ksfraser\FaBankImport\Import\Queries\InsertAuditLog;
use Ksfraser\FaBankImport\Import\Validators\TransactionValidator;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;
use Ksfraser\FaBankImport\Import\Exceptions\BankTransferException;
use Ksfraser\FaBankImport\Import\Exceptions\ImportException;

/**
 * TransactionImportTransaction: Atomic wrapper for single transaction processing.
 * 
 * Responsibility: Execute complete transaction import (validation → insert → audit)
 * within a single SAVEPOINT transaction. Handles rollback on error.
 * 
 * **CRITICAL FOR DATA INTEGRITY**: Ensures all-or-nothing semantics per transaction.
 * Detects same-account transfers via TransactionValidator (non-recoverable).
 * Creates audit trail for all operations.
 * 
 * Usage:
 *   $txn = new TransactionImportTransaction($dbManager);
 *   $result = $txn->execute(
 *       $transactionData,
 *       $statementId,
 *       $bankAccountId,
 *       ['create_gl_entry' => true, 'create_audit' => true]
 *   );
 */
class TransactionImportTransaction
{
    private TransactionDatabaseManager $dbManager;
    private InsertBiTransaction $insertTransaction;
    private TransactionValidator $validator;
    private UpdateTransactionStatus $statusUpdater;
    private InsertAuditLog $auditLogger;

    /**
     * Initialize transaction transaction wrapper with dependencies.
     *
     * @param TransactionDatabaseManager $dbManager Database manager with SAVEPOINT support
     * @param InsertBiTransaction $insertTransaction Transaction insert query
     * @param TransactionValidator $validator Transaction validation rules
     * @param UpdateTransactionStatus $statusUpdater Transaction status updater
     * @param InsertAuditLog $auditLogger Audit log recorder
     */
    public function __construct(
        TransactionDatabaseManager $dbManager,
        InsertBiTransaction $insertTransaction,
        TransactionValidator $validator,
        UpdateTransactionStatus $statusUpdater,
        InsertAuditLog $auditLogger
    ) {
        $this->dbManager = $dbManager;
        $this->insertTransaction = $insertTransaction;
        $this->validator = $validator;
        $this->statusUpdater = $statusUpdater;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Execute complete transaction import with full rollback on any error.
     *
     * @param array $transactionData Transaction data to import
     * @param int $statementId Parent statement ID
     * @param int $bankAccountId Bank account ID for validation
     * @param array $options Processing options (create_gl_entry, create_audit, etc)
     * @return TransactionProcessResult Transaction result with status and audit ID
     * @throws BankTransferException On same-account transfer (NON-RECOVERABLE)
     * @throws TransactionProcessingException On database errors
     */
    public function execute(
        array $transactionData,
        int $statementId,
        int $bankAccountId,
        array $options = []
    ): TransactionProcessResult {

        $result = new TransactionProcessResult();
        $savepointName = 'sp_txn_import_' . time() . '_' . rand(10000, 99999);
        $beforeState = $transactionData;

        try {
            // Create savepoint for this transaction
            $this->dbManager->createSavepoint($savepointName);

            // Validate transaction (throws on critical errors)
            $validationResult = $this->validator->validate($transactionData, $bankAccountId, $options);

            if (!$validationResult->isValid()) {
                $this->dbManager->rollbackToSavepoint($savepointName);
                throw new TransactionProcessingException(
                    'Transaction validation failed',
                    context: [
                        'errors' => $validationResult->getErrors(),
                        'statement_id' => $statementId
                    ]
                );
            }

            // Insert transaction record
            $insertResult = $this->insertTransaction->execute(
                $transactionData,
                $statementId,
                $bankAccountId,
                $options
            );

            $transactionId = $insertResult->getData()['transaction_id'];

            // Update status to processing
            $this->statusUpdater->execute($transactionId, 'processing', [
                'notes' => 'Imported from bank statement'
            ]);

            // Create audit entry if requested
            if ($options['create_audit'] ?? true) {
                $this->auditLogger->execute(
                    'transaction_imported',
                    $transactionId,
                    [], // No before-state for new record
                    $transactionData, // After-state
                    [
                        'reason' => 'Automatic import',
                        'statement_id' => $statementId,
                        'bank_account_id' => $bankAccountId
                    ]
                );
            }

            // Record success
            $result->setData([
                'transaction_id' => $transactionId,
                'statement_id' => $statementId,
                'bank_account_id' => $bankAccountId,
                'status' => 'processing',
                'imported_at' => date('Y-m-d H:i:s')
            ]);

            return $result;

        } catch (BankTransferException $e) {
            // Same-account transfer: non-recoverable, but rollback anyway
            if ($this->dbManager->isActive()) {
                $this->dbManager->rollbackToSavepoint($savepointName);
            }
            $result->addError('Same-account transfer detected and rejected');
            throw $e;

        } catch (ImportException $e) {
            // Other import exceptions: rollback
            if ($this->dbManager->isActive()) {
                $this->dbManager->rollbackToSavepoint($savepointName);
            }
            throw $e;

        } catch (\Exception $e) {
            // Unexpected errors: rollback
            if ($this->dbManager->isActive()) {
                $this->dbManager->rollbackToSavepoint($savepointName);
            }
            throw new TransactionProcessingException(
                'Unexpected error in transaction import: ' . $e->getMessage(),
                context: [
                    'statement_id' => $statementId,
                    'bank_account_id' => $bankAccountId,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }
}
