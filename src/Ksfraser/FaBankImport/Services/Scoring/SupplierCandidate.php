<?php

/**
 * Supplier Candidate Interface
 *
 * Defines minimal interface for supplier/vendor candidates that can be
 * scored by the ScoringRuleEngine. Both KeywordMatch and VendorCandidate
 * implement this interface to allow flexible scoring.
 *
 * @package    Ksfraser\FaBankImport\Services\Scoring
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

/**
 * Interface for supplier/vendor candidates
 *
 * Defines the minimal methods required by ScoringRuleEngine
 * and individual scoring rules.
 *
 * @since 7.6
 */
interface SupplierCandidate
{
    /**
     * Get supplier/partner ID
     *
     * @return int
     */
    public function getPartnerId(): int;

    /**
     * Get supplier/partner name
     *
     * @return string
     */
    public function getPartnerName(): string;

    /**
     * Get supplier/partner type
     *
     * @return int
     */
    public function getPartnerType(): int;
}
