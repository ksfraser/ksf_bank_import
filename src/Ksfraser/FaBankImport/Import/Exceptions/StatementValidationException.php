<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a bank statement fails validation checks.
 * 
 * Examples:
 * - Statement has no transactions
 * - Statement dates are invalid
 * - Statement already imported
 */
class StatementValidationException extends ImportException
{
    protected bool $recoverable = true;

    /**
     * Create validation exception for missing statements.
     *
     * @return self
     */
    public static function noTransactions(): self
    {
        return new self(
            'Bank statement contains no transactions',
            1001
        );
    }

    /**
     * Create validation exception for invalid date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return self
     */
    public static function invalidDateRange(string $startDate, string $endDate): self
    {
        return new self(
            "Invalid date range: {$startDate} to {$endDate}",
            1002,
            null,
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
    }

    /**
     * Create validation exception for duplicate statement.
     *
     * @param int $statementId
     * @param string $sourceFile
     * @return self
     */
    public static function duplicateStatement(int $statementId, string $sourceFile): self
    {
        return new self(
            "Statement {$statementId} already imported from {$sourceFile}",
            1003,
            null,
            ['statement_id' => $statementId, 'source_file' => $sourceFile]
        );
    }

    /**
     * Create validation exception for missing required fields.
     *
     * @param array $missingFields
     * @return self
     */
    public static function missingFields(array $missingFields): self
    {
        return new self(
            'Statement missing required fields: ' . implode(', ', $missingFields),
            1004,
            null,
            ['missing_fields' => $missingFields]
        );
    }
}
