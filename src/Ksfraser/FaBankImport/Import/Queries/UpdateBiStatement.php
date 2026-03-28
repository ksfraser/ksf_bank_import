<?php

namespace Ksfraser\FaBankImport\Import\Queries;

use Ksfraser\FaBankImport\Import\Results\OperationResult;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\StatementValidationException;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;

/**
 * UpdateBiStatement: Update statement record in bank_import_statements table.
 * 
 * Responsibility: Atomically update statement metadata (status, dates, totals) with
 * validation and error handling. Supports partial updates via options.
 * 
 * Usage:
 *   $updater = new UpdateBiStatement($dbManager);
 *   $result = $updater->execute($statementId, ['status' => 'processed', 'end_date' => '2025-01-31']);
 */
class UpdateBiStatement
{
    private TransactionDatabaseManager $dbManager;

    /**
     * Initialize statement updater.
     *
     * @param TransactionDatabaseManager $dbManager Database transaction manager
     */
    public function __construct(TransactionDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Update statement with optional fields and validation.
     *
     * @param int $statementId Statement ID to update
     * @param array $data Update data (status, start_date, end_date, total_amount, reconciled_at, notes)
     * @param array $options Processing options (validate_dates, validate_amounts)
     * @return OperationResult Success/failure with audit trails
     * @throws StatementValidationException On validation errors
     * @throws TransactionProcessingException On database errors
     */
    public function execute(int $statementId, array $data, array $options = []): OperationResult
    {
        if ($statementId <= 0) {
            throw new StatementValidationException(
                'Invalid statement ID: ' . $statementId,
                context: ['statement_id' => $statementId]
            );
        }

        $result = new OperationResult();

        try {
            // Fetch existing record for comparison
            $existing = db_fetch_assoc(
                "SELECT `id`, `status`, `start_date`, `end_date`, `total_amount` FROM `bank_import_statements` WHERE `id` = %s",
                $statementId
            );

            if (!$existing) {
                throw new StatementValidationException(
                    'Statement not found: ' . $statementId,
                    context: ['statement_id' => $statementId]
                );
            }

            // Validate incoming data if requested
            if ($options['validate_dates'] ?? false) {
                $this->validateDateFields($data, $existing);
            }
            if ($options['validate_amounts'] ?? false) {
                $this->validateAmountFields($data);
            }

            // Build update clause with allowed fields
            $allowedFields = [
                'status' => 'string',
                'end_date' => 'date',
                'total_amount' => 'float',
                'reconciled_at' => 'datetime',
                'notes' => 'string',
                'bank_reference' => 'string',
                'import_completed_at' => 'datetime'
            ];

            $updateData = [];
            $updateData['updated_at'] = date('Y-m-d H:i:s'); // Always update timestamp

            foreach ($allowedFields as $field => $type) {
                if (isset($data[$field])) {
                    $value = $data[$field];

                    // Type-specific validation
                    switch ($type) {
                        case 'string':
                            $updateData[$field] = (string)$value;
                            break;
                        case 'date':
                            $this->validateDate($value, "Date field {$field}");
                            $updateData[$field] = (string)$value;
                            break;
                        case 'datetime':
                            $this->validateDatetime($value, "DateTime field {$field}");
                            $updateData[$field] = (string)$value;
                            break;
                        case 'float':
                            $updateData[$field] = (float)$value;
                            break;
                    }
                }
            }

            // Build SQL
            $setClause = implode(', ', array_map(
                fn ($key) => "`{$key}` = %s",
                array_keys($updateData)
            ));

            $query = "UPDATE `bank_import_statements` SET {$setClause} WHERE `id` = %s";

            // Prepare arguments
            $args = array_values($updateData);
            $args[] = $statementId;

            // Execute update
            $affected = db_query($query, ...$args);

            if ($affected === false) {
                throw new TransactionProcessingException(
                    'Failed to update statement',
                    context: [
                        'statement_id' => $statementId,
                        'query_error' => db_error()
                    ]
                );
            }

            // Record audit trail
            $result->setData([
                'statement_id' => $statementId,
                'old_values' => [
                    'status' => $existing['status'],
                    'end_date' => $existing['end_date'],
                    'total_amount' => $existing['total_amount']
                ],
                'new_values' => array_intersect_key(
                    $updateData,
                    ['status' => 1, 'end_date' => 1, 'total_amount' => 1]
                ),
                'updated_fields' => array_keys($updateData),
                'rows_affected' => 1,
                'timestamp' => $updateData['updated_at']
            ]);

            return $result;

        } catch (StatementValidationException | TransactionProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransactionProcessingException(
                'Unexpected error updating statement: ' . $e->getMessage(),
                context: [
                    'statement_id' => $statementId,
                    'exception_type' => get_class($e)
                ]
            );
        }
    }

    /**
     * Validate date range consistency.
     *
     * @param array $data Update data containing date fields
     * @param array $existing Existing record for comparison
     * @throws StatementValidationException On validation error
     */
    private function validateDateFields(array $data, array $existing): void
    {
        $startDate = $data['start_date'] ?? $existing['start_date'] ?? null;
        $endDate = $data['end_date'] ?? $existing['end_date'] ?? null;

        if ($startDate && $endDate && $startDate > $endDate) {
            throw new StatementValidationException(
                'Statement date range invalid: start_date must be before or equal to end_date',
                context: ['start_date' => $startDate, 'end_date' => $endDate]
            );
        }
    }

    /**
     * Validate amount fields.
     *
     * @param array $data Update data containing amount fields
     * @throws StatementValidationException On validation error
     */
    private function validateAmountFields(array $data): void
    {
        if (isset($data['total_amount'])) {
            $amount = $data['total_amount'];
            
            if (!is_numeric($amount)) {
                throw new StatementValidationException(
                    'Total amount must be numeric',
                    context: ['provided_value' => $amount]
                );
            }

            if ($amount < 0) {
                throw new StatementValidationException(
                    'Total amount cannot be negative',
                    context: ['provided_value' => $amount]
                );
            }

            if ($amount > 999999999.99) {
                throw new StatementValidationException(
                    'Total amount exceeds maximum allowed: 999,999,999.99',
                    context: ['provided_value' => $amount]
                );
            }
        }
    }

    /**
     * Validate date format.
     *
     * @param string $date Date string (YYYY-MM-DD format)
     * @param string $fieldName Field name for error message
     * @throws StatementValidationException On validation error
     */
    private function validateDate(string $date, string $fieldName): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new StatementValidationException(
                "{$fieldName} must be in YYYY-MM-DD format",
                context: ['provided_value' => $date]
            );
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            throw new StatementValidationException(
                "{$fieldName} is not a valid date",
                context: ['provided_value' => $date]
            );
        }
    }

    /**
     * Validate datetime format.
     *
     * @param string $datetime DateTime string (YYYY-MM-DD HH:MM:SS format)
     * @param string $fieldName Field name for error message
     * @throws StatementValidationException On validation error
     */
    private function validateDatetime(string $datetime, string $fieldName): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime)) {
            throw new StatementValidationException(
                "{$fieldName} must be in YYYY-MM-DD HH:MM:SS format",
                context: ['provided_value' => $datetime]
            );
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if (!$dt || $dt->format('Y-m-d H:i:s') !== $datetime) {
            throw new StatementValidationException(
                "{$fieldName} is not a valid datetime",
                context: ['provided_value' => $datetime]
            );
        }
    }
}
