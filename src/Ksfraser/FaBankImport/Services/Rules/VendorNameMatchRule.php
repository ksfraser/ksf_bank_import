<?php

/**
 * Vendor Name Matching Rule
 *
 * Uses keyword scoring for vendor name matching. Higher score for exact
 * or near-exact matches, lower scores for partial matches.
 *
 * @package    Ksfraser\FaBankImport\Services\Rules
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services\Rules;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRule;
use Ksfraser\FaBankImport\Services\Scoring\SupplierCandidate;

/**
 * @BABOK RTM: BR-001 / ARCH-001; file: Services/Rules/VendorNameMatchRule.php; coverage: docs/TestCases/ (file rollup)
 * Vendor Name Keyword Scoring Rule
 *
 * @since 7.6
 */
class VendorNameMatchRule implements ScoringRule
{
    /**
 * @BABOK BR-001 / ARCH-001 — method: calculateScore
     * @inheritDoc
     */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        $description = strtoupper($transaction['memo'] ?? $transaction['description'] ?? '');
        $vendor_name = strtoupper($match->getPartnerName() ?? '');

        if (empty($description) || empty($vendor_name)) {
            return 0;
        }

        // Exact match
        if (strpos($description, $vendor_name) !== false) {
            return 30.0;
        }

        // Partial match scoring
        $words = explode(' ', $vendor_name);
        $matches = 0;

        foreach ($words as $word) {
            if (!empty($word) && strpos($description, $word) !== false) {
                $matches++;
            }
        }

        if ($matches === 0) {
            return -5.0;
        }

        // Score based on word match percentage
        return ($matches / count($words)) * 30.0;
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getName
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'vendor_name';
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getMaxBoost
     * @inheritDoc
     */
    public function getMaxBoost(): float
    {
        return 30.0;
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getMinReduction
     * @inheritDoc
     */
    public function getMinReduction(): float
    {
        return -5.0;
    }
}
