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

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Scoring\SupplierCandidate;

/**
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
     * {@inheritdoc}
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * {@inheritdoc}
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * {@inheritdoc}
     */
    public function getPartnerType(): int
    {
        return $this->partnerType;
    }

    /**
     * Get partner account
     *
     * @return string
     */
    public function getPartnerAccount(): string
    {
        return $this->partnerAccount;
    }
}
