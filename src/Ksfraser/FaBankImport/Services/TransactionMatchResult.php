<?php

/**
 * Transaction Match Result
 *
 * Immutable value object representing a single partner match result.
 * Used by TransactionPartnerMatcher to represent matches across all
 * partner types (supplier, customer, bank transfer).
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

/**
 * Transaction Match Result
 *
 * Represents a single matched partner with confidence score and type.
 * Immutable value object.
 *
 * @since 7.6
 */
final class TransactionMatchResult
{
    /**
     * Partner ID (supplier_id, customer_id, or bank_account_id)
     *
     * @var int
     */
    private int $partnerId;

    /**
     * Partner name
     *
     * @var string
     */
    private string $partnerName;

    /**
     * Match confidence score (0-100)
     *
     * @var float
     */
    private float $score;

    /**
     * Partner type: 'supplier', 'customer', or 'bank_transfer'
     *
     * @var string
     */
    private string $partnerType;

    /**
     * Constructor
     *
     * @param int    $partnerId    The partner ID
     * @param string $partnerName  The partner name
     * @param float  $score        The confidence score (0-100)
     * @param string $partnerType  The partner type
     */
    public function __construct(
        int $partnerId,
        string $partnerName,
        float $score,
        string $partnerType
    ) {
        $this->partnerId = $partnerId;
        $this->partnerName = $partnerName;
        $this->score = max(0.0, min(100.0, $score)); // Clamp to 0-100
        $this->partnerType = $partnerType;
    }

    /**
     * Get partner ID
     *
     * @return int
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * Get partner name
     *
     * @return string
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * Get confidence score (0-100)
     *
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * Get partner type
     *
     * @return string One of: 'supplier', 'customer', 'bank_transfer'
     */
    public function getPartnerType(): string
    {
        return $this->partnerType;
    }

    /**
     * Check if match meets minimum confidence threshold
     *
     * @param int $threshold Minimum score threshold
     * @return bool
     */
    public function meetsThreshold(int $threshold): bool
    {
        return $this->score >= $threshold;
    }

    /**
     * Convert to array for display/serialization
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'partner_id' => $this->partnerId,
            'partner_name' => $this->partnerName,
            'score' => $this->score,
            'partner_type' => $this->partnerType,
        ];
    }
}
