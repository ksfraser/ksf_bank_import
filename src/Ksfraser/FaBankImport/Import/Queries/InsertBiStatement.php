<?php

namespace Ksfraser\FaBankImport\Import\Queries;

use Ksfraser\FaBankImport\Import\Results\StatementImportResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\StatementValidationException;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;

/**
 * InsertBiStatement: Insert validated statement record into bank_import_statements table.
 * 
 * Responsibility: Atomically insert statement with duplicate detection, date validation,
 * and transaction counting. Returns comprehensive result with statement ID and metadata.
 * 
 * CRITICAL: Must run BEFORE InsertBiTransaction to ensure statement exists.
 * Uses SAVEPOINT for nested transaction support.
 * 
 * Usage:
 *   $inserter = new InsertBiStatement($dbManager);
 *   $result = $inserter->execute($statementData, $bankAccountId, ['check_duplicates' => true]);
 */
class InsertBiStatement
{
    private TransactionDatabaseManager $dbManager;

    /**
     * Initialize statement inserter.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager with SAVEPOINT support
     */
    public function __construct(TransactionDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Insert statement record with validation and duplicate detection.
     *
     * @param array $statementData Statement data (id, date, end_date, reference, transactions[], etc)
     * @param int $bankAccountId Bank account ID for this import
     * @param array $options Insert options (check_duplicates, source_file, file_hash)
     * @return StatementImportResult Statement ID, metadata, and transaction tracking
     * @throws StatementValidationException On validation errors
     * @throws TransactionProcessingException On database errors
     */
    public function execute(
        array $statementData,
        int $bankAccountId,
        array $options = []
    ): StatementImportResult {

        $result = new StatementImportResult();

        try {
            // Validate required fields
            $this->validateRequiredFields($statementData);

            // Check for duplicates if requested
            if ($options['check_duplicates'] ?? true) {
                $this->checkDuplicate($statementData, $bankAccountId);
            }

            // Extract and normalize data
            $insertData = [
                'bank_account_id' => $bankAccountId,
                'import_date' => $statementData['date'],
                'statement_end_date' => $statementData['end_date'] ?? $statementData['date'],
                'bank_reference' => trim((string)($statementData['reference'] ?? '')),
                'total_transactions' => count($statementData['transactions'] ?? []),
                'total_amount' => $this->calculateTotal($statementData['transactions'] ?? []),
                'status' => 'processing',
                'created_at' => date('Y-m-d H:i:s'),
                'source_file' => trim((string)($options['source_file'] ?? 'unknown')),
            ];

            // Add optional file hash for duplicate detection
            if (isset($options['file_hash']) && !empty($options['file_hash'])) {
                $insertData['file_hash'] = (string)$options['file_hash'];
            }

            // Build insert statement
            $columns = array_keys($insertData);
            $placeholders = array_fill(0, count($columns), '%s');

            $columnList = implode('`, `', $columns);
            $placeholderList = implode(', ', $placeholders);

            $query = "INSERT INTO `bank_import_statements` (`{$columnList}`) VALUES ({$placeholderList})";

            // Create savepoint for this operation
            $savepointName = 'sp_insert_statement_' . time() . '_' . rand(1000, 9999);
            
            try {
                $this->dbManager->createSavepoint($savepointName);

                // Execute insert
                $stmtId = db_query($query, ...array_values($insertData));

                if ($stmtId === false || $stmtId === 0) {
                    $this->dbManager->rollbackToSavepoint($savepointName);
                    throw new TransactionProcessingException(
                        'Failed to insert statement',
                        context: [
                            'bank_account_id' => $bankAccountId,
                            'statement_date' => $statementData['date'],
                            'query_error' => db_error()
                        ]
                    );
                }

                // Record successful insertion
                $result->setStatementId($stmtId);
                $result->setData([
                    'statement_id' => $stmtId,
                    'bank_account_id' => $bankAccountId,
                    'import_date' => $insertData['import_date'],
                    'statement_end_date' => $insertData['statement_end_date'],
                    'total_transactions' => $insertData['total_transactions'],
                    'total_amount' => $insertData['total_amount'],
                    'transaction_ids' => [], // Will be populated by InsertBiTransaction
                    'created_at' => $insertData['created_at']
                ]);

                return $result;

            } catch (\Exception $e) {
                if ($this->dbManager->isActive()) {
                    $this->dbManager->rollbackToSavepoint($savepointName);
                }
                throw $e;
            }

        } catch (StatementValidationException | TransactionProcessingException $e) {
            $result->addError((string)$e);
            throw $e;
        } catch (\Exception $e) {
            throw new TransactionProcessingException(
                'Unexpected error inserting statement: ' . $e->getMessage(),
                context: [
                    'bank_account_id' => $bankAccountId,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }

    /**
     * Validate required statement fields before insert.
     *
     * @param array $statementData Statement data to validate
     * @throws StatementValidationException On validation error
     */
    private function validateRequiredFields(array $statementData): void
    {
        $requiredFields = ['date', 'transactions'];
        foreach ($requiredFields as $field) {
            if (!isset($statementData[$field]) || empty($statementData[$field])) {
                throw new StatementValidationException(
                    "Required field missing: {$field}",
                    context: ['field' => $field]
                );
            }
        }

        // Validate date is in proper format
        $date = $statementData['date'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new StatementValidationException(
                'Statement date must be in YYYY-MM-DD format',
                context: ['provided_date' => $date]
            );
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            throw new StatementValidationException(
                'Statement date is not valid',
                context: ['provided_date' => $date]
            );
        }

        // Validate transactions is array
        if (!is_array($statementData['transactions'])) {
            throw new StatementValidationException(
                'Transactions must be an array',
                context: ['provided_type' => gettype($statementData['transactions'])]
            );
        }
    }

    /**
     * Check for duplicate statement import.
     *
     * @param array $statementData Statement to check for duplicates
     * @param int $bankAccountId Bank account ID
     * @throws StatementValidationException If duplicate found
     */
    private function checkDuplicate(array $statementData, int $bankAccountId): void
    {
        // Check by date and bank account
        $duplicate = db_fetch_assoc(
            "SELECT `id`, `bank_reference` FROM `bank_import_statements` 
             WHERE `bank_account_id` = %s AND `import_date` = %s AND `status` != %s",
            $bankAccountId,
            $statementData['date'],
            'cancelled'
        );

        if ($duplicate) {
            throw new StatementValidationException(
                'Duplicate statement already imported for this date',
                context: [
                    'bank_account_id' => $bankAccountId,
                    'import_date' => $statementData['date'],
                    'existing_id' => $duplicate['id'],
                    'recoverable' => false
                ]
            );
        }

        // Check by file hash if provided
        if (isset($statementData['file_hash']) && !empty($statementData['file_hash'])) {
            $hashDuplicate = db_fetch_assoc(
                "SELECT `id` FROM `bank_import_statements` WHERE `file_hash` = %s AND `status` != %s",
                $statementData['file_hash'],
                'cancelled'
            );

            if ($hashDuplicate) {
                throw new StatementValidationException(
                    'Statement file already imported based on file hash',
                    context: [
                        'file_hash' => $statementData['file_hash'],
                        'existing_id' => $hashDuplicate['id'],
                        'recoverable' => false
                    ]
                );
            }
        }
    }

    /**
     * Calculate total amount from transactions.
     *
     * @param array $transactions Array of transaction data with 'amount' field
     * @return float Total of all transaction amounts
     */
    private function calculateTotal(array $transactions): float
    {
        $total = 0.0;
        foreach ($transactions as $txn) {
            if (isset($txn['amount']) && is_numeric($txn['amount'])) {
                $total += (float)$txn['amount'];
            }
        }
        return round($total, 2);
    }
}
