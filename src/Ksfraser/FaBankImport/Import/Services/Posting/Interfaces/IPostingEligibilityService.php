<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

/**
 * Contract for determining posting eligibility.
 * THIS IS A PURE FUNCTION - no state, no dependencies, deterministic output.
 * 
 * Rules:
 * - APPROVED status → ELIGIBLE (copy to main table)
 * - REJECTED status → SKIP (don't copy, archive)
 * - INVESTIGATE status → SKIP (don't copy, hold for manual review)
 * - PENDING status → HOLD (awaiting review completion)
 * - Amount must be > 0 and <= 1,000,000
 */
interface IPostingEligibilityService
{
    // Status constants
    const STATUS_ELIGIBLE = 'ELIGIBLE';    // Ready to copy to main table
    const STATUS_SKIP = 'SKIP';            // Don't copy (rejected/investigate)
    const STATUS_HOLD = 'HOLD';            // Hold for manual review (bad amount, pending)
    const STATUS_ERROR = 'ERROR';          // Invalid state

    /**
     * Determine if transaction should be posted (copied to main table).
     * Pure function: no side effects, deterministic result.
     * 
     * @param string $decisionStatus Review decision status (APPROVED, REJECTED, INVESTIGATE, PENDING)
     * @param float $amount Transaction amount
     * 
     * @return string One of STATUS_* constants
     */
    public function determineEligibility(string $decisionStatus, float $amount): string;
}
