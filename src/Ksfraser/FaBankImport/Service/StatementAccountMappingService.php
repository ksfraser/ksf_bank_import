<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :StatementAccountMappingService [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for StatementAccountMappingService.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * StatementAccountMappingService
 *
 * Pure helpers for working with parsed statement objects.
 *
 * This service is intentionally framework-agnostic so it can be unit-tested
 * without a FrontAccounting runtime.
 */
class StatementAccountMappingService
{
    /**
     * Collect unique detected account identifiers per uploaded file index.
     *
     * Detection rule:
     * - Prefer $statement->acctid when present/non-empty
     * - Otherwise fall back to $statement->account
     *
     * @param array<int, array<int|string, object>> $multistatements
     * @return array<int, array<int, string>> fileIndex => list of detected account strings
     */
    public function collectDetectedAccountsByFile(array $multistatements): array
    {
        $result = [];

        foreach ($multistatements as $fileIndex => $statements) {
            $seen = [];
            foreach ((array)$statements as $statement) {
                $detected = $this->getDetectedAccount($statement);
                if ($detected === null || $detected === '') {
                    continue;
                }
                if (!isset($seen[$detected])) {
                    $seen[$detected] = true;
                }
            }
            $result[(int)$fileIndex] = array_keys($seen);
        }

        return $result;
    }

    /**
     * Apply a detected->target mapping to statement->account, leaving acctid untouched.
     *
     * @param array<int, array<int|string, object>> $multistatements
     * @param array<string, string> $detectedToTargetAccountNumber detected => FA bank_account_number
     * @return array<int, array<int|string, object>> updated multistatements
     */
    public function applyAccountNumberMapping(array $multistatements, array $detectedToTargetAccountNumber): array
    {
        foreach ($multistatements as $fileIndex => $statements) {
            foreach ((array)$statements as $statementIndex => $statement) {
                $detected = $this->getDetectedAccount($statement);
                if ($detected === null || $detected === '') {
                    continue;
                }

                if (isset($detectedToTargetAccountNumber[$detected])) {
                    $statement->account = $detectedToTargetAccountNumber[$detected];
                    $multistatements[$fileIndex][$statementIndex] = $statement;
                }
            }
        }

        return $multistatements;
    }

    /**
     * Get detected account for a statement object.
     *
     * @param object $statement
     * @return string|null
     */
    public function getDetectedAccount(object $statement): ?string
    {
        $acctid = $statement->acctid ?? null;
        if (is_string($acctid) && trim($acctid) !== '') {
            return $acctid;
        }

        $account = $statement->account ?? null;
        if (is_string($account) && trim($account) !== '') {
            return $account;
        }

        return null;
    }
}
