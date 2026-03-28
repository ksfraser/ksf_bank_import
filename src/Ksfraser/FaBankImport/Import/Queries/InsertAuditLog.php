<?php

namespace Ksfraser\FaBankImport\Import\Queries;

use Ksfraser\FaBankImport\Import\Results\OperationResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;

/**
 * InsertAuditLog: Record import/modification audit trail for corrections and disputes.
 * 
 * Responsibility: Create immutable audit log entries capturing before/after snapshots
 * for transaction corrections, statement adjustments, and data modifications.
 * Critical for compliance and traceability.
 * 
 * Usage:
 *   $logger = new InsertAuditLog($dbManager);
 *   $result = $logger->execute(
 *       'transaction_corrected',
 *       123,
 *       ['amount' => 100.00],
 *       ['amount' => 110.00],
 *       ['reason' => 'Correction for fee']
 *   );
 */
class InsertAuditLog
{
    private TransactionDatabaseManager $dbManager;

    /**
     * Initialize audit logging system.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager
     */
    public function __construct(TransactionDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Record audit log entry with before/after snapshots.
     *
     * @param string $action Action performed (transaction_corrected, voided, gl_reposted, amount_adjusted, status_changed)
     * @param int|null $recordId ID of affected record (transaction_id or statement_id)
     * @param array $beforeState Before snapshot (original values)
     * @param array $afterState After snapshot (modified values)
     * @param array $metadata Additional context (reason, user_id, ip_address, session_id)
     * @return OperationResult Success/failure with audit log ID
     * @throws TransactionProcessingException On database errors
     */
    public function execute(
        string $action,
        ?int $recordId = null,
        array $beforeState = [],
        array $afterState = [],
        array $metadata = []
    ): OperationResult {

        $result = new OperationResult();

        try {
            // Validate action against allowed list
            $this->validateAction($action);

            // Build insert data
            $insertData = [
                'action' => strtolower(trim($action)),
                'affected_record_id' => $recordId,
                'before_state' => json_encode($beforeState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'after_state' => json_encode($afterState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Add optional metadata
            if (isset($metadata['reason']) && !empty($metadata['reason'])) {
                $insertData['reason'] = (string)$metadata['reason'];
            }
            if (isset($metadata['user_id']) && $metadata['user_id'] > 0) {
                $insertData['user_id'] = (int)$metadata['user_id'];
            }
            if (isset($metadata['ip_address']) && !empty($metadata['ip_address'])) {
                $insertData['ip_address'] = (string)$metadata['ip_address'];
            }
            if (isset($metadata['session_id']) && !empty($metadata['session_id'])) {
                $insertData['session_id'] = (string)$metadata['session_id'];
            }
            if (isset($metadata['change_summary']) && !empty($metadata['change_summary'])) {
                $insertData['change_summary'] = (string)$metadata['change_summary'];
            }

            // Build column list and placeholders
            $columns = array_keys($insertData);
            $placeholders = array_fill(0, count($columns), '%s');

            $columnList = implode('`, `', $columns);
            $placeholderList = implode(', ', $placeholders);

            $query = "INSERT INTO `bank_import_audit_log` (`{$columnList}`) VALUES ({$placeholderList})";

            // Execute insert
            $result_id = db_query($query, ...array_values($insertData));

            if ($result_id === false || $result_id === 0) {
                throw new TransactionProcessingException(
                    'Failed to insert audit log entry',
                    context: [
                        'action' => $action,
                        'record_id' => $recordId,
                        'query_error' => db_error()
                    ]
                );
            }

            // Record success
            $result->setData([
                'audit_log_id' => $result_id,
                'action' => $action,
                'affected_record_id' => $recordId,
                'before_fields' => count($beforeState),
                'after_fields' => count($afterState),
                'reason' => $metadata['reason'] ?? 'Not provided',
                'timestamp' => $insertData['created_at']
            ]);

            return $result;

        } catch (TransactionProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransactionProcessingException(
                'Unexpected error inserting audit log: ' . $e->getMessage(),
                context: [
                    'action' => $action,
                    'record_id' => $recordId,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }

    /**
     * Validate action against allowed audit actions.
     *
     * @param string $action Action to validate
     * @throws TransactionProcessingException If action is invalid
     */
    private function validateAction(string $action): void
    {
        $allowedActions = [
            'transaction_corrected',
            'transaction_voided',
            'gl_reposted',
            'amount_adjusted',
            'status_changed',
            'statement_reconciled',
            'contact_resolved',
            'duplicate_detected',
            'error_corrected',
            'import_completed',
            'import_failed',
            'manual_override',
            'batch_adjusted'
        ];

        $action = strtolower(trim($action));

        if (!in_array($action, $allowedActions)) {
            throw new TransactionProcessingException(
                'Invalid audit action: ' . $action,
                context: [
                    'provided_action' => $action,
                    'allowed_actions' => $allowedActions
                ]
            );
        }
    }

    /**
     * Get list of allowed audit actions.
     *
     * @return array List of valid action values
     */
    public function getAllowedActions(): array
    {
        return [
            'transaction_corrected',
            'transaction_voided',
            'gl_reposted',
            'amount_adjusted',
            'status_changed',
            'statement_reconciled',
            'contact_resolved',
            'duplicate_detected',
            'error_corrected',
            'import_completed',
            'import_failed',
            'manual_override',
            'batch_adjusted'
        ];
    }

    /**
     * Calculate change summary from before/after states.
     *
     * @param array $before Before state snapshot
     * @param array $after After state snapshot
     * @return string Human-readable change summary
     */
    public static function calculateChangeSummary(array $before, array $after): string
    {
        $changes = [];

        // Detect changed fields
        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        }

        // Detect removed fields
        foreach ($before as $key => $value) {
            if (!isset($after[$key])) {
                $changes[] = "{$key}: removed";
            }
        }

        return implode('; ', $changes) ?: 'No changes detected';
    }
}
