<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

/**
 * Duplicate Review Handler - Phase 2
 * 
 * Manages the duplicate review workflow:
 * 1. Accepts flagged duplicates from import process
 * 2. Stores them in bi_transactions_dupe staging table
 * 3. Provides query interface for dashboard filtering
 * 
 * Responsibility:
 * - Insert flagged duplicates into bi_transactions_dupe
 * - Track field differences and match type
 * - Maintain audit trail (reviewed_by, reviewed_at, notes)
 * - Support filtering and pagination for dashboard
 */
class DuplicateReviewHandler
{
    /**
     * Store a flagged duplicate for user review.
     *
     * When code+acctid match but fields differ (or fuzzy match needs user decision),
     * we store the incoming transaction in bi_transactions_dupe for review.
     *
     * PHASE 2 WORKFLOW:
     * 1. Import detects duplicate (exact code + field mismatch OR fuzzy)
     * 2. Calls this method with incoming transaction + match details
     * 3. Transaction stored with status='PENDING' + matched ID
     * 4. User sees dashboard with side-by-side comparison
     * 5. User confirms or rejects
     * 6. On confirm: move to statement, create journal entry
     * 7. On reject: do nothing (skip import)
     *
     * @param array $incomingTransaction New transaction from import
     * @param array $matchedTransaction Existing matched transaction (from bi_transactions)
     * @param string $matchType ENUM: 'EXACT_CODE_MISMATCH' | 'FUZZY_MATCH'
     * @param string $fieldsThatDiffer CSV string (e.g., "memo,amount") or empty for fuzzy
     * @param int $statementId Statement being imported
     * @return int ID of created duplicate record
     * @throws \Exception If insertion fails
     */
    public function storeForReview(
        array $incomingTransaction,
        array $matchedTransaction,
        string $matchType,
        string $fieldsThatDiffer,
        int $statementId
    ): int {
        // Map incoming transaction to bi_transactions_dupe schema
        $dupeRecord = $this->mapToStageTable($incomingTransaction);
        
        // Add Phase 2 metadata
        $dupeRecord['matching_bi_transaction_id'] = $matchedTransaction['id'] ?? null;
        $dupeRecord['fields_that_differ'] = $fieldsThatDiffer ?? '';
        $dupeRecord['match_type'] = $matchType;  // EXACT_CODE_MISMATCH|FUZZY_MATCH
        $dupeRecord['status'] = 'PENDING';
        $dupeRecord['reviewed_by'] = null;
        $dupeRecord['reviewed_at'] = null;
        $dupeRecord['notes'] = null;
        $dupeRecord['created_at'] = date('Y-m-d H:i:s');
        $dupeRecord['updated_at'] = date('Y-m-d H:i:s');
        $dupeRecord['statement_id'] = $statementId;
        
        // Insert into staging table
        return $this->insertDupRecord($dupeRecord);
    }

    /**
     * Map transaction fields to bi_transactions_dupe schema.
     *
     * bi_transactions_dupe is a full clone of bi_transactions plus metadata.
     * This method copies all applicable fields from incoming transaction.
     *
     * @param array $transaction Fields from incoming import
     * @return array Mapped record ready for insertion
     */
    private function mapToStageTable(array $transaction): array
    {
        // Define which fields to copy from transaction to bi_transactions_dupe
        // These must match bi_transactions schema (excluding Phase 2 metadata)
        $fieldsToCopy = [
            'id',
            'statement_id',
            'valueTimestamp',
            'transactionAmount',
            'merchant',
            'memo',
            'reference',
            'transactionCode',
            'acctid',
            'transactiontype',
            'createdAt',
            'updatedAt',
            'notes',
            'reconciliation_status'
        ];
        
        $mapped = [];
        foreach ($fieldsToCopy as $field) {
            if (isset($transaction[$field])) {
                $mapped[$field] = $transaction[$field];
            }
        }
        
        return $mapped;
    }

    /**
     * Insert duplicate record into bi_transactions_dupe.
     *
     * @param array $record Record with all fields + metadata
     * @return int Insert ID
     * @throws \Exception If query fails
     */
    private function insertDupRecord(array $record): int
    {
        try {
            $cols = array_keys($record);
            $vals = array_values($record);
            
            // Build column list
            $colList = implode(', ', array_map(function($c) {
                return sprintf('%s.%s', TB_PREF, $c);
            }, $cols));
            
            // Build value placeholders
            $placeholders = implode(', ', array_fill(0, count($vals), '%s'));
            
            // Escape values
            $escaped = array_map(function($v) {
                return db_escape($v);
            }, $vals);
            
            $sql = sprintf(
                "INSERT INTO %sbi_transactions_dupe (%s) VALUES (%s)",
                TB_PREF,
                $colList,
                $placeholders
            );
            
            db_query($sql, 'Could not insert duplicate record');
            
            // Get last insert ID
            return db_insert_id();
        } catch (\Throwable $e) {
            throw new \Exception(
                "Failed to store duplicate for review: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get pending duplicates for review (dashboard query).
     *
     * Supports filtering by:
     * - Status (PENDING, CONFIRMED_DUPE, MOVED_TO_STATEMENT, REJECTED)
     * - Match type (EXACT_CODE_MISMATCH, FUZZY_MATCH)
     * - Bank account
     * - Date range
     * - Pagination
     *
     * @param array $filters Filtering options
     * @return array Array of duplicate records ready for dashboard display
     */
    public function getPendingDuplicates(array $filters = []): array
    {
        $where = [];
        $params = [];
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = sprintf("%sbi_transactions_dupe.status = %s", TB_PREF, db_escape($filters['status']));
        } else {
            // Default: show only PENDING
            $where[] = sprintf("%sbi_transactions_dupe.status = 'PENDING'", TB_PREF);
        }
        
        // Match type filter
        if (!empty($filters['match_type'])) {
            $where[] = sprintf("%sbi_transactions_dupe.match_type = %s", TB_PREF, db_escape($filters['match_type']));
        }
        
        // Bank account filter
        if (!empty($filters['acctid'])) {
            $where[] = sprintf("%sbi_transactions_dupe.acctid = %s", TB_PREF, db_escape($filters['acctid']));
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where[] = sprintf("%sbi_transactions_dupe.valueTimestamp >= %s", TB_PREF, db_escape($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $where[] = sprintf("%sbi_transactions_dupe.valueTimestamp <= %s", TB_PREF, db_escape($filters['date_to']));
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Pagination
        $limit = (int)($filters['limit'] ?? 20);
        $offset = (int)($filters['offset'] ?? 0);
        
        // Build query with ORDER BY created_at DESC (newest first)
        $sql = sprintf(
            "SELECT 
                %sbi_transactions_dupe.* 
            FROM %sbi_transactions_dupe 
            %s
            ORDER BY %sbi_transactions_dupe.created_at DESC 
            LIMIT %d OFFSET %d",
            TB_PREF,
            TB_PREF,
            $whereClause,
            TB_PREF,
            $limit,
            $offset
        );
        
        try {
            $result = db_query($sql, 'Could not fetch pending duplicates');
            $records = [];
            while ($row = db_fetch_assoc($result)) {
                $records[] = $row;
            }
            return $records;
        } catch (\Throwable $e) {
            throw new \Exception(
                "Failed to fetch pending duplicates: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get duplicate record by ID with paired transaction.
     *
     * Returns both the duplicate record and the matched transaction for
     * side-by-side comparison on dashboard.
     *
     * @param int $dupeId ID of duplicate record in bi_transactions_dupe
     * @return array Array with keys:
     *   - 'dupe': the duplicate record (pending)
     *   - 'matched': the original transaction from bi_transactions
     *   - 'fields_that_differ': parsed array of differing fields
     */
    public function getDuplicatePair(int $dupeId): array
    {
        try {
            // Get duplicate record
            $sql = sprintf(
                "SELECT * FROM %sbi_transactions_dupe WHERE id = %d LIMIT 1",
                TB_PREF,
                $dupeId
            );
            $result = db_query($sql, 'Could not fetch duplicate record');
            $dupe = db_fetch_assoc($result);
            
            if (!$dupe) {
                throw new \Exception("Duplicate record not found: {$dupeId}");
            }
            
            // Get matched transaction
            $matchedId = $dupe['matching_bi_transaction_id'] ?? null;
            $matched = null;
            
            if ($matchedId) {
                $sql = sprintf(
                    "SELECT * FROM %sbi_transactions WHERE id = %d LIMIT 1",
                    TB_PREF,
                    $matchedId
                );
                $result = db_query($sql, 'Could not fetch matched transaction');
                $matched = db_fetch_assoc($result);
            }
            
            // Parse fields_that_differ from CSV
            $fieldsThatDiffer = [];
            if (!empty($dupe['fields_that_differ'])) {
                $fieldsThatDiffer = explode(',', $dupe['fields_that_differ']);
            }
            
            return [
                'dupe' => $dupe,
                'matched' => $matched,
                'fields_that_differ' => $fieldsThatDiffer
            ];
        } catch (\Throwable $e) {
            throw new \Exception(
                "Failed to fetch duplicate pair: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Update duplicate status after user review.
     *
     * @param int $dupeId Duplicate record ID
     * @param string $status New status: CONFIRMED_DUPE|MOVED_TO_STATEMENT|REJECTED
     * @param string|null $reviewer User ID or name
     * @param string|null $notes User notes/comments
     * @return bool Success
     */
    public function updateReviewStatus(
        int $dupeId,
        string $status,
        ?string $reviewer = null,
        ?string $notes = null
    ): bool {
        try {
            $updates = [
                sprintf("status = %s", db_escape($status)),
                sprintf("reviewed_at = %s", db_escape(date('Y-m-d H:i:s'))),
                sprintf("updated_at = %s", db_escape(date('Y-m-d H:i:s')))
            ];
            
            if ($reviewer !== null) {
                $updates[] = sprintf("reviewed_by = %s", db_escape($reviewer));
            }
            
            if ($notes !== null) {
                $updates[] = sprintf("notes = %s", db_escape($notes));
            }
            
            $sql = sprintf(
                "UPDATE %sbi_transactions_dupe SET %s WHERE id = %d",
                TB_PREF,
                implode(', ', $updates),
                $dupeId
            );
            
            db_query($sql, 'Could not update duplicate review status');
            return true;
        } catch (\Throwable $e) {
            throw new \Exception(
                "Failed to update duplicate review status: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
