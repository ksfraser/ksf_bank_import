<?php

/**
 * Vendor Candidate Class
 *
 * Simple implementation of SupplierCandidate interface for testing
 * and representing vendor/supplier candidates in matching processes.
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Scoring\SupplierCandidate;

/**
 * @BABOK CLASS ROLLUP: VendorCandidate — 5 methods; BR-001 (5); BR-002 (5); FR-008 (5)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/VendorCandidate.php; coverage: docs/TestCases/ (file rollup)
 * Vendor Candidate Implementation
 *
 * Simple implementation of the SupplierCandidate interface for
 * representing vendor/supplier candidates in matching operations.
 */
final class VendorCandidate implements SupplierCandidate
{
    /**
     * Partner/Supplier ID
     *
     * @var int
     */
    private int $partnerId;

    /**
     * Partner/Supplier name
     *
     * @var string
     */
    private string $partnerName;

    /**
     * Partner/Supplier account number
     *
     * @var string
     */
    private string $partnerAccount;

    /**
     * Partner type (defaults to 1 for simplicity)
     *
     * @var int
     */
    private int $partnerType;

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct
     * Constructor
     *
     * @param int    $partnerId      The partner/supplier ID
     * @param string $partnerName    The partner/supplier name
     * @param string $partnerAccount The partner/supplier account
     * @param int    $partnerType    The partner type (default: 1)
     */
    public function __construct(
        int $partnerId,
        string $partnerName,
        string $partnerAccount,
        int $partnerType = 1
    ) {
        $this->partnerId = $partnerId;
        $this->partnerName = $partnerName;
        $this->partnerAccount = $partnerAccount;
        $this->partnerType = $partnerType;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerId
     * {@inheritdoc}
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerName
     * {@inheritdoc}
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerType
     * {@inheritdoc}
     */
    public function getPartnerType(): int
    {
        return $this->partnerType;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerAccount
     * Get partner account
     *
     * @return string
     */
    public function getPartnerAccount(): string
    {
        return $this->partnerAccount;
    }
}
