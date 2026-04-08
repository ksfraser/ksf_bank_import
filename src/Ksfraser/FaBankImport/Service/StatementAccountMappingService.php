<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * Statement Account Mapping Service - Manages account number mapping for statements
 * 
 * Collects detected accounts from statements and applies account number mappings
 */
final class StatementAccountMappingService
{
    /**
     * Collect detected account IDs from multiple statement files
     * 
     * Prefers 'acctid' property over 'account' property
     * Processes multi-file statement arrays
     * 
     * @param array $multistatements Array of [file_index => [statement_objects]]
     * @return array Array of [file_index => [account_ids]]
     */
    public function collectDetectedAccountsByFile(array $multistatements): array
    {
        $result = [];
        
        foreach ($multistatements as $fileIndex => $statements) {
            $accounts = [];
            
            if (is_array($statements)) {
                foreach ($statements as $statement) {
                    if (is_object($statement)) {
                        // Prefer acctid, fall back to account
                        $account = $statement->acctid ?? $statement->account ?? null;
                        if ($account && !in_array($account, $accounts, true)) {
                            $accounts[] = $account;
                        }
                    }
                }
            }
            
            $result[$fileIndex] = $accounts;
        }
        
        return $result;
    }
    
    /**
     * Apply account number mapping to statements
     * 
     * Updates the 'account' property with mapped value if mapping exists
     * Keeps 'acctid' unchanged
     * Returns new statement objects with mapped values
     * 
     * @param array $multistatements Array of [file_index => [statement_objects]]
     * @param array $mapping Array of [detected_account => fa_account_number]
     * @return array Modified statement array
     */
    public function applyAccountNumberMapping(array $multistatements, array $mapping): array
    {
        $result = [];
        
        foreach ($multistatements as $fileIndex => $statements) {
            $result[$fileIndex] = [];
            
            if (is_array($statements)) {
                foreach ($statements as $statement) {
                    if (is_object($statement)) {
                        // Get the account to look up
                        $accountToMap = $statement->acctid ?? $statement->account ?? null;
                        
                        // Check if we have a mapping for this account
                        if ($accountToMap && isset($mapping[$accountToMap])) {
                            // Clone the object and update account
                            $updated = clone $statement;
                            $updated->account = $mapping[$accountToMap];
                            $result[$fileIndex][] = $updated;
                        } else {
                            // No mapping, keep original
                            $result[$fileIndex][] = $statement;
                        }
                    }
                }
            }
        }
        
        return $result;
    }
}
