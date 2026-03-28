<?php

namespace Ksfraser\FaBankImport\Import\Queries;

use Ksfraser\FaBankImport\Import\Results\OperationResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * UpdateTransactionStatus: Update transaction processing status and state.
 * 
 * Responsibility: Atomically update transaction status in bank_import_transactions table
 * with audit trail support. Handles status transitions: pending → processing → completed/failed.
 * 
 * Usage:
 *   $updater = new UpdateTransactionStatus($dbManager);
 *   $result = $updater->execute($transactionId, 'completed', ['notes' => 'GL posted']);
 */
class UpdateTransactionStatus
{
    private TransactionDatabaseManager $dbManager;

    /**
     * Initialize transaction status updater.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager
     */
    public function __construct(TransactionDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Update transaction status with optional metadata.
     *
     * @param int $transactionId Transaction ID to update
     * @param string $status New status (pending, processing, completed, failed, skipped)
     * @param array $metadata Optional metadata (notes, gl_entry_id, partner_id, etc)
     * @return OperationResult Success/failure with context
     * @throws TransactionProcessingException On database errors
     * @throws TransactionFetchException If transaction not found
     */
    public function execute(int $transactionId, string $status = 'completed', array $metadata = []): OperationResult
    {
        if ($transactionId <= 0) {
            throw new TransactionFetchException(
                'Invalid transaction ID: ' . $transactionId,
                context: ['transaction_id' => $transactionId]
            );
        }

        $result = new OperationResult();

        try {
            // Verify transaction exists
            $existingTxn = db_fetch_assoc(
                "SELECT `id`, `status` FROM `bank_import_transactions` WHERE `id` = %s",
                $transactionId
            );

            if (!$existingTxn) {
                throw new TransactionFetchException(
                    'Transaction not found: ' . $transactionId,
                    context: ['transaction_id' => $transactionId]
                );
            }

            // Build update data
            $updateData = [
                'status' => $this->normalizeStatus($status),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Add metadata fields if provided
            if (isset($metadata['notes']) && !empty($metadata['notes'])) {
                $updateData['processing_notes'] = (string)$metadata['notes'];
            }
            if (isset($metadata['gl_entry_id']) && $metadata['gl_entry_id'] > 0) {
                $updateData['gl_entry_id'] = (int)$metadata['gl_entry_id'];
            }
            if (isset($metadata['partner_id']) && $metadata['partner_id'] > 0) {
                $updateData['partner_id'] = (int)$metadata['partner_id'];
            }
            if (isset($metadata['amount_posted'])) {
                $updateData['amount_posted'] = (float)$metadata['amount_posted'];
            }
            if (isset($metadata['error_message']) && !empty($metadata['error_message'])) {
                $updateData['error_message'] = (string)$metadata['error_message'];
            }

            // Build SQL with placeholders for all fields
            $setClause = implode(', ', array_map(
                fn ($key) => "`{$key}` = %s",
                array_keys($updateData)
            ));

            $query = "UPDATE `bank_import_transactions` SET {$setClause} WHERE `id` = %s";

            // Prepare arguments
            $args = array_values($updateData);
            $args[] = $transactionId;

            // Execute update
            $affected = db_query($query, ...$args);

            if ($affected === false) {
                throw new TransactionProcessingException(
                    'Failed to update transaction status',
                    context: [
                        'transaction_id' => $transactionId,
                        'new_status' => $status,
                        'old_status' => $existingTxn['status'] ?? 'unknown',
                        'query_error' => db_error()
                    ]
                );
            }

            // Record results
            $result->setData([
                'transaction_id' => $transactionId,
                'old_status' => $existingTxn['status'],
                'new_status' => $status,
                'updated_fields' => array_keys($updateData),
                'rows_affected' => 1,
                'timestamp' => $updateData['updated_at']
            ]);

            return $result;

        } catch (TransactionFetchException | TransactionProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransactionProcessingException(
                'Unexpected error updating transaction status: ' . $e->getMessage(),
                context: [
                    'transaction_id' => $transactionId,
                    'status' => $status,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }

    /**
     * Validate and normalize status to valid values.
     *
     * @param string $status Raw status value
     * @return string Normalized status
     */
    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        
        $validStatuses = [
            'pending',
            'processing',
            'completed',
            'failed',
            'skipped',
            'cancelled'
        ];

        return in_array($status, $validStatuses) ? $status : 'pending';
    }

    /**
     * Get valid status transitions from current status.
     *
     * @param string $currentStatus Current transaction status
     * @return array List of valid target statuses
     */
    public function getValidTransitions(string $currentStatus): array
    {
        $transitions = [
            'pending' => ['processing', 'skipped', 'cancelled'],
            'processing' => ['completed', 'failed', 'cancelled'],
            'completed' => ['failed'], // Can fail completed if error found
            'failed' => ['processing', 'cancelled'], // Can retry
            'skipped' => ['processing', 'cancelled'],
            'cancelled' => [] // Terminal state
        ];

        return $transitions[strtolower($currentStatus)] ?? [];
    }
}
