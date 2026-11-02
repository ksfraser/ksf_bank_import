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

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services\Scoring;

/**
 * @BABOK CLASS ROLLUP: SupplierCandidate — 3 methods; BR-011 (3)
 * @BABOK RTM: BR-011 / ARCH-011; file: Services/Scoring/SupplierCandidate.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK BR-011 / ARCH-011 — method: getPartnerId
     * Get supplier/partner ID
     *
     * @return int
     */
    public function getPartnerId(): int;

    /**
 * @BABOK BR-011 / ARCH-011 — method: getPartnerName
     * Get supplier/partner name
     *
     * @return string
     */
    public function getPartnerName(): string;

    /**
 * @BABOK BR-011 / ARCH-011 — method: getPartnerType
     * Get supplier/partner type
     *
     * @return int
     */
    public function getPartnerType(): int;
}
