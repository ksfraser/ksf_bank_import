<?php

namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateReviewHandler;

/**
 * Duplicate Review Controller - Phase 2
 * 
 * Handles HTTP POST actions from the duplicate review dashboard:
 * - Confirm duplicate (move to appropriate location)
 * - Reject duplicate (skip import)
 * - Create whitelist rule (allow future similar duplicates)
 * - Add notes/comments to review
 * 
 * This controller bridges the UI (DuplicateReviewView) with the business logic
 * (DuplicateReviewHandler).
 */
class DuplicateReviewController
{
    private $handler;
    
    public function __construct(DuplicateReviewHandler $handler = null)
    {
        $this->handler = $handler ?? new DuplicateReviewHandler();
    }
    
    /**
     * Route POST request to appropriate action.
     *
     * Expected $_POST fields:
     * - action: 'confirm'|'reject'|'move'|'whitelist'
     * - dupe_id: ID of duplicate record
     * - notes: (optional) User notes
     * - merchant_pattern: (optional) For whitelist action
     * - rule_name: (optional) For whitelist action
     *
     * @return array Response with 'success' boolean and 'message'
     */
    public function handleRequest(): array
    {
        $action = $_POST['action'] ?? null;
        $dupeId = (int)($_POST['dupe_id'] ?? 0);
        $notes = $_POST['notes'] ?? null;
        $reviewer = $this->getCurrentUser();
        
        if (!$action || !$dupeId) {
            return ['success' => false, 'message' => 'Missing action or dupe_id'];
        }
        
        try {
            switch ($action) {
                case 'confirm':
                    return $this->confirmDuplicate($dupeId, $reviewer, $notes);
                case 'reject':
                    return $this->rejectDuplicate($dupeId, $reviewer, $notes);
                case 'move':
                    return $this->moveToDuplicateStatement($dupeId, $reviewer, $notes);
                case 'whitelist':
                    return $this->createWhitelistRule(
                        $_POST['merchant_pattern'] ?? '',
                        $_POST['rule_name'] ?? '',
                        $notes
                    );
                default:
                    return ['success' => false, 'message' => "Unknown action: $action"];
            }
        } catch (\Throwable $e) {
            error_log("DuplicateReviewController error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Confirm that flagged transactions are indeed duplicates.
     *
     * Phase 2 flow:
     * 1. Mark bi_transactions_dupe record as CONFIRMED_DUPE
     * 2. Set reviewed_by and reviewed_at
     * 3. Do NOT import the incoming transaction
     * 4. Leave original in bi_transactions
     *
     * @param int $dupeId Duplicate record ID
     * @param string $reviewer Current user/reviewer
     * @param string|null $notes User notes
     * @return array Response
     */
    private function confirmDuplicate(int $dupeId, ?string $reviewer, ?string $notes): array
    {
        $this->handler->updateReviewStatus(
            $dupeId,
            'CONFIRMED_DUPE',
            $reviewer,
            $notes
        );
        
        return [
            'success' => true,
            'message' => 'Duplicate confirmed. Incoming transaction will not be imported.',
            'dupe_id' => $dupeId,
            'action' => 'confirm'
        ];
    }
    
    /**
     * Reject a duplicate (mark as false positive).
     *
     * Phase 2 flow:
     * 1. Mark bi_transactions_dupe record as REJECTED
     * 2. Set reviewed_by and reviewed_at
     * 3. Do NOT import the incoming transaction (validation error)
     * 4. User must manually decide what to do (edit and re-import, etc.)
     *
     * @param int $dupeId Duplicate record ID
     * @param string $reviewer Current user/reviewer
     * @param string|null $notes User notes
     * @return array Response
     */
    private function rejectDuplicate(int $dupeId, ?string $reviewer, ?string $notes): array
    {
        $this->handler->updateReviewStatus(
            $dupeId,
            'REJECTED',
            $reviewer,
            $notes ?? 'Rejected as false positive'
        );
        
        return [
            'success' => true,
            'message' => 'Duplicate rejected as false positive.',
            'dupe_id' => $dupeId,
            'action' => 'reject'
        ];
    }
    
    /**
     * Move duplicate to "moved to statement" status.
     *
     * Phase 2 flow:
     * 1. Mark bi_transactions_dupe record as MOVED_TO_STATEMENT
     * 2. Set reviewed_by and reviewed_at
     * 3. Transaction is flagged but available for user to import to different statement
     *
     * @param int $dupeId Duplicate record ID
     * @param string $reviewer Current user/reviewer
     * @param string|null $notes User notes
     * @return array Response
     */
    private function moveToDuplicateStatement(int $dupeId, ?string $reviewer, ?string $notes): array
    {
        $this->handler->updateReviewStatus(
            $dupeId,
            'MOVED_TO_STATEMENT',
            $reviewer,
            $notes ?? 'Marked for different statement'
        );
        
        return [
            'success' => true,
            'message' => 'Transaction marked for different statement.',
            'dupe_id' => $dupeId,
            'action' => 'move'
        ];
    }
    
    /**
     * Create a whitelist rule to allow future similar duplicates.
     *
     * Stores a pattern in bi_duplicate_rules that will auto-allow
     * fuzzy matches from this merchant in the future.
     *
     * @param string $merchantPattern Merchant name pattern (e.g., "SHOPPERS%")
     * @param string $ruleName Human-readable rule name
     * @param string|null $notes Admin notes
     * @return array Response
     */
    private function createWhitelistRule(string $merchantPattern, string $ruleName, ?string $notes): array
    {
        if (empty($merchantPattern) || empty($ruleName)) {
            return ['success' => false, 'message' => 'Merchant pattern and rule name required'];
        }
        
        try {
            $sql = sprintf(
                "INSERT INTO %sbi_duplicate_rules (merchant_pattern, rule_name, allow_duplicates, active, notes)
                 VALUES (%s, %s, 1, 1, %s)",
                TB_PREF,
                db_escape($merchantPattern),
                db_escape($ruleName),
                db_escape($notes ?? '')
            );
            
            db_query($sql, 'Could not create whitelist rule');
            
            return [
                'success' => true,
                'message' => "Whitelist rule created: $ruleName",
                'pattern' => $merchantPattern
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to create whitelist rule: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get current user/reviewer identifier.
     *
     * Override this to integrate with your authentication system.
     *
     * @return string Current user ID or session identifier
     */
    private function getCurrentUser(): string
    {
        // For FA systems, this might be $_SESSION['__LOGIN__'] or similar
        if (isset($_SESSION['__LOGIN__'])) {
            return (string)$_SESSION['__LOGIN__'];
        }
        
        // Fallback to session ID
        return session_id();
    }
    
    /**
     * Get statistics about duplicate reviews for admin dashboard.
     *
     * @return array Statistics: pending_count, confirmed_count, rejected_count, etc.
     */
    public function getReviewStatistics(): array
    {
        try {
            $sql = sprintf(
                "SELECT 
                    status,
                    COUNT(*) as count
                FROM %sbi_transactions_dupe
                GROUP BY status",
                TB_PREF
            );
            
            $result = db_query($sql, 'Could not fetch review statistics');
            $stats = [
                'pending' => 0,
                'confirmed' => 0,
                'rejected' => 0,
                'moved' => 0,
                'total' => 0
            ];
            
            while ($row = db_fetch_assoc($result)) {
                $status = $row['status'] ?? 'UNKNOWN';
                $count = (int)($row['count'] ?? 0);
                $stats['total'] += $count;
                
                switch ($status) {
                    case 'PENDING':
                        $stats['pending'] = $count;
                        break;
                    case 'CONFIRMED_DUPE':
                        $stats['confirmed'] = $count;
                        break;
                    case 'REJECTED':
                        $stats['rejected'] = $count;
                        break;
                    case 'MOVED_TO_STATEMENT':
                        $stats['moved'] = $count;
                        break;
                }
            }
            
            return $stats;
        } catch (\Throwable $e) {
            error_log("Failed to fetch review statistics: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
