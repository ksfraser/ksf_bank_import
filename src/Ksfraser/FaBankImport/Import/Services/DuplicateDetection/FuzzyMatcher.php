<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Level 2: Fuzzy Matcher
 * 
 * Finds potential duplicates when Level 1 (exact code match) fails.
 * Matches on: exact date + amount (±$0.01) + merchant/memo
 * 
 * NOTE: No date window. Re-downloads have identical dates.
 * Cross-statement reconciliation is a different flow.
 * 
 * Responsibility: Find candidate duplicate transactions for whitelist rule checking
 */
class FuzzyMatcher
{
    /**
     * Find fuzzy matches on date + amount + merchant/memo.
     *
     * @param array $transaction Transaction with: valueTimestamp, transactionAmount, merchant, memo, acctid
     * @return array Array of matching transactions (0-N)
     * @throws TransactionFetchException If query fails
     */
    public function find(array $transaction): array
    {
        $date = $transaction['valueTimestamp'] ?? null;
        $amount = $transaction['transactionAmount'] ?? 0;
        $merchant = $transaction['merchant'] ?? null;
        $memo = $transaction['memo'] ?? null;
        $acctid = $transaction['acctid'] ?? null;
        
        // All required for fuzzy match
        if (!$date || !$acctid) {
            return [];
        }
        
        try {
            // Build match criteria: merchant or memo or accountName
            $matchCriteria = "(";
            $criteria = [];
            
            if ($merchant) {
                $criteria[] = "merchant = " . db_escape($merchant);
            }
            if ($memo) {
                $criteria[] = "memo = " . db_escape($memo);
            }
            if (isset($transaction['accountName'])) {
                $criteria[] = "accountName = " . db_escape($transaction['accountName']);
            }
            
            if (empty($criteria)) {
                return [];  // Can't match without any merchant/memo/accountName
            }
            
            $matchCriteria .= implode(" OR ", $criteria) . ")";
            
            $query = sprintf(
                "SELECT * FROM %s bi_transactions
                 WHERE acctid = %s
                 AND valueTimestamp = %s
                 AND ABS(transactionAmount - %f) < 0.01
                 AND %s
                 ORDER BY id DESC",
                TB_PREF,
                db_escape($acctid),
                db_escape($date),
                (float)$amount,
                $matchCriteria
            );
            
            $results = [];
            $res = db_query($query, 'Could not query for fuzzy duplicates');
            
            while ($row = db_fetch_assoc($res)) {
                $results[] = $row;
            }
            
            return $results;
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_transactions WHERE date=? AND amount=? AND merchant|memo=?",
                $e->getMessage()
            );
        }
    }
}
