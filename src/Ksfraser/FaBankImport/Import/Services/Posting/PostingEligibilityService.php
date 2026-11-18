<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting;

use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;

/**
 * Pure function service determining if a duplicate transaction is eligible for posting.
 * 
 * Implements business rules:
 * - Only APPROVED duplicates are copied to main transaction table
 * - REJECTED duplicates are archived (not copied)
 * - INVESTIGATE duplicates are skipped (pending manual review)
 * - PENDING duplicates are held (awaiting review)
 * - Amount must be positive and within limits
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
final class PostingEligibilityService implements IPostingEligibilityService
{
    private const MAX_AMOUNT = 1000000.00;
    private const MIN_AMOUNT = 0.01;

    /**
     * Determine posting eligibility based on review decision and amount.
     * No dependencies, no side effects - pure function.
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function determineEligibility(string $decisionStatus, float $amount): string
    {
        // Validate amount first (applies to all statuses)
        if ($amount <= 0.00 || $amount > self::MAX_AMOUNT) {
            return self::STATUS_HOLD;
        }

        // Route based on decision status
        switch ($decisionStatus) {
            case 'APPROVED':
                return self::STATUS_ELIGIBLE;
            case 'REJECTED':
            case 'INVESTIGATE':
                return self::STATUS_SKIP;
            case 'PENDING':
                return self::STATUS_HOLD;
            default:
                return self::STATUS_ERROR;
        }
    }
}
