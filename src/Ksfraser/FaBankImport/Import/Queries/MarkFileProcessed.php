<?php

namespace Ksfraser\FaBankImport\Import\Queries;

use Ksfraser\FaBankImport\Import\Results\OperationResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;

/**
 * MarkFileProcessed: Update file import status to "processed".
 * 
 * Responsibility: Set file processing status after successful import completion.
 * 
 * Usage:
 *   $marker = new MarkFileProcessed($dbManager);
 *   $result = $marker->execute($fileId, 'processed', $additionalData);
 */
class MarkFileProcessed
{
    private TransactionDatabaseManager $dbManager;

    /**
     * Initialize with database manager for atomic operations.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager
     */
    public function __construct(TransactionDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Mark file as processed with optional metadata.
     *
     * @param int $fileId File ID to update
     * @param string $status Processing status (processed, failed, pending, skipped)
     * @param array $metadata Optional metadata (import_count, error_count, duration, etc)
     * @return OperationResult Success/failure with audit trails
     * @throws TransactionProcessingException On database errors
     */
    public function execute(int $fileId, string $status = 'processed', array $metadata = []): OperationResult
    {
        if ($fileId <= 0) {
            throw new TransactionProcessingException(
                'Invalid file ID: ' . $fileId,
                context: ['file_id' => $fileId]
            );
        }

        $result = new OperationResult();

        try {
            // Build update data
            $updateData = [
                'status' => $this->normalizeStatus($status),
                'processed_at' => date('Y-m-d H:i:s'),
            ];

            // Add metadata fields
            if (isset($metadata['import_count'])) {
                $updateData['statements_imported'] = (int)$metadata['import_count'];
            }
            if (isset($metadata['error_count'])) {
                $updateData['statements_failed'] = (int)$metadata['error_count'];
            }
            if (isset($metadata['duration'])) {
                $updateData['processing_duration_ms'] = (int)$metadata['duration'];
            }
            if (isset($metadata['file_hash'])) {
                $updateData['file_hash'] = (string)$metadata['file_hash'];
            }

            // Build SQL update statement
            $setClause = implode(', ', array_map(
                fn ($key) => "`{$key}` = %s",
                array_keys($updateData)
            ));

            $query = "UPDATE `bank_import_files` SET {$setClause} WHERE `id` = %s";

            // Prepare arguments for db_query
            $args = array_values($updateData);
            $args[] = $fileId;

            // Execute update (use db_query from FA framework)
            $affected = db_query($query, ...$args);

            if ($affected === false) {
                throw new TransactionProcessingException(
                    'Failed to update file status',
                    context: [
                        'file_id' => $fileId,
                        'status' => $status,
                        'query_error' => db_error()
                    ]
                );
            }

            // Record what was updated
            $result->setData([
                'file_id' => $fileId,
                'status' => $status,
                'updated_fields' => array_keys($updateData),
                'rows_affected' => 1,
                'timestamp' => $updateData['processed_at']
            ]);

            return $result;

        } catch (TransactionProcessingException $e) {
            $result->addError((string)$e);
            $result->addContext('file_id', $fileId);
            $result->addContext('status', $status);
            throw $e;
        } catch (\Exception $e) {
            throw new TransactionProcessingException(
                'Unexpected error marking file processed: ' . $e->getMessage(),
                context: [
                    'file_id' => $fileId,
                    'status' => $status,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }

    /**
     * Normalize status to valid values.
     *
     * @param string $status Raw status value
     * @return string Normalized status
     */
    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        
        $validStatuses = [
            'processed',
            'failed',
            'pending',
            'skipped',
            'partial'
        ];

        return in_array($status, $validStatuses) ? $status : 'processed';
    }
}
