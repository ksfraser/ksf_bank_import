<?php

/**
 * Supplier Transaction Matcher Adapter
 *
 * Bridges legacy vendor_list array format with the new SupplierMatcher service.
 * Converts legacy data structures to the modern SupplierMatcher interface.
 *
 * LEGACY FORMAT:
 * ```
 * [
 *   'shortnames' => ['acc1', 'acc2', ...],
 *   0 => ['supplier_id' => 1, 'supp_name' => 'ACME', ...],
 *   1 => ['supplier_id' => 2, 'supp_name' => 'Other', ...],
 *   ...
 * ]
 * ```
 *
 * NEW FORMAT:
 * ```
 * SupplierMatcher expects:
 * - transaction: ['account' => string, 'amount' => float, 'memo' => string]
 * - candidates: [KeywordMatch, KeywordMatch, ...]
 * ```
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
 * Supplier Transaction Matcher - Adapter for Legacy Vendor List
 *
 * Allows legacy code using vendor_list arrays to benefit from the new
 * SupplierMatcher service without full refactoring.
 *
 * @since 7.6
 */
class SupplierTransactionMatcher
{
    /**
     * SupplierMatcher service instance
     *
     * @var SupplierMatcher
     */
    private SupplierMatcher $matcher;

    /**
     * Configuration for supplier matching
     *
     * @var SupplierMatchingConfiguration
     */
    private SupplierMatchingConfiguration $configuration;

    /**
     * Legacy vendor list array format
     *
     * @var array
     */
    private array $vendorList;

    /**
     * Map of matched vendor index to supplier matches
     *
     * @var array
     */
    private array $matchResults = [];

    /**
     * Constructor
     *
     * @param array $vendorList Legacy vendor list format
     * @param SupplierMatchingConfiguration|null $configuration Configuration (uses PROD defaults if null)
     * @since 7.6
     */
    public function __construct(
        array $vendorList,
        ?SupplierMatchingConfiguration $configuration = null
    ) {
        $this->vendorList = $vendorList;
        $this->configuration = $configuration ?? new SupplierMatchingConfiguration();
        
        // Initialize SupplierMatcher with configuration
        $confidenceEnhancer = new ConfidenceEnhancer(
            new \Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine()
        );
        $this->matcher = new SupplierMatcher($this->configuration, $confidenceEnhancer);
    }

    /**
     * Match transaction to suppliers using legacy vendor_list format
     *
     * Converts legacy array format to SupplierMatcher format, performs matching,
     * and returns matched vendor index (for backward compatibility).
     *
     * @param array $transaction Transaction data with keys: 'account', 'amount', 'memo', etc.
     * @return int|false Matched vendor list index, or false if no match
     * @since 7.6
     */
    public function matchTransaction(array $transaction): int|false
    {
        // Convert legacy vendor list to KeywordMatch candidates
        $candidates = $this->convertVendorListToCandidates();
        
        if (empty($candidates)) {
            return false;
        }

        // Normalize transaction data for SupplierMatcher
        $normalizedTransaction = $this->normalizeTransaction($transaction);

        // Use SupplierMatcher to score candidates
        $result = $this->matcher->matchSuppliers($normalizedTransaction, $candidates);

        // Cache result for later retrieval
        $this->matchResults[$normalizedTransaction['account']] = $result;

        // Return legacy format: matched vendor index or false
        if ($result->isNoMatch()) {
            return false;
        }

        $bestMatch = $result->getBestMatch();
        if (!$bestMatch) {
            return false;
        }

        // Find vendor index by supplier ID
        return $this->findVendorIndexBySupplier((int)$bestMatch['supplier_id']);
    }

    /**
     * Get match decision (auto/manual/no_match) for last matched transaction
     *
     * Returns the auto-match decision from the last matchTransaction call.
     *
     * @param string $accountKey Transaction account identifier
     * @return string Decision: 'auto', 'manual', or 'no_match'
     * @since 7.6
     */
    public function getMatchDecision(string $accountKey): string
    {
        return $this->matchResults[$accountKey]?->getDecision() ?? 'no_match';
    }

    /**
     * Get all match candidates for last matched transaction
     *
     * Returns full match results from SupplierMatcher for UI decision-making.
     *
     * @param string $accountKey Transaction account identifier
     * @return array Match results with confidence scores
     * @since 7.6
     */
    public function getMatchResults(string $accountKey): array
    {
        return $this->matchResults[$accountKey]?->toArray() ?? [
            'matches' => [],
            'decision' => 'no_match',
            'match_count' => 0,
            'best_supplier_id' => null
        ];
    }

    /**
     * Convert legacy vendor_list array to KeywordMatch candidates
     *
     * Transforms the legacy vendor list format into KeywordMatch objects
     * suitable for SupplierMatcher.
     *
     * @return array<KeywordMatch> List of supplier candidates
     * @since 7.6
     */
    private function convertVendorListToCandidates(): array
    {
        $candidates = [];
        
        // Legacy format: vendor_list['shortnames'] is indexed, details at vendor_list[$i]
        if (!isset($this->vendorList['shortnames']) || !is_array($this->vendorList['shortnames'])) {
            return $candidates;
        }

        foreach ($this->vendorList['shortnames'] as $index => $bankAccount) {
            // Skip the 'shortnames' key itself
            if (!is_numeric($index)) {
                continue;
            }

            // Get vendor details from legacy array
            if (!isset($this->vendorList[$index])) {
                continue;
            }

            $vendorData = $this->vendorList[$index];
            $supplierId = (int)($vendorData['supplier_id'] ?? 0);
            $supplierName = (string)($vendorData['supp_name'] ?? '');

            if ($supplierId <= 0) {
                continue;
            }

            // Create VendorCandidate to represent supplier
            $candidate = new VendorCandidate($supplierId, $supplierName, $bankAccount);
            $candidates[] = $candidate;
        }

        return $candidates;
    }

    /**
     * Normalize transaction data for SupplierMatcher
     *
     * Converts whatever transaction format is provided into the format
     * expected by SupplierMatcher and ScoringRuleEngine.
     *
     * @param array $transaction Raw transaction data
     * @return array Normalized transaction data
     * @since 7.6
     */
    private function normalizeTransaction(array $transaction): array
    {
        return [
            'account' => (string)($transaction['account'] ?? $transaction['otherBankAccount'] ?? ''),
            'amount' => (float)($transaction['amount'] ?? $transaction['transactionAmount'] ?? 0),
            'memo' => (string)($transaction['memo'] ?? $transaction['transactionTitle'] ?? ''),
            'partner_account' => (string)($transaction['account'] ?? $transaction['otherBankAccount'] ?? ''),
            'partner_amount' => (float)($transaction['amount'] ?? $transaction['transactionAmount'] ?? 0),
        ];
    }

    /**
     * Find vendor list index by supplier ID
     *
     * Maps supplier ID back to the vendor_list array index for legacy compatibility.
     *
     * @param int $supplierId Supplier ID to find
     * @return int|false Vendor list index or false if not found
     * @since 7.6
     */
    private function findVendorIndexBySupplier(int $supplierId): int|false
    {
        // Legacy vendor_list indexes are numeric (0, 1, 2, ...)
        // with 'shortnames' as a special key
        foreach ($this->vendorList as $index => $vendorData) {
            if ($index === 'shortnames' || !is_array($vendorData)) {
                continue;
            }

            if (($vendorData['supplier_id'] ?? 0) == $supplierId) {
                return (int)$index;
            }
        }

        return false;
    }

    /**
     * Get configuration reference
     *
     * @return SupplierMatchingConfiguration Current configuration
     * @since 7.6
     */
    public function getConfiguration(): SupplierMatchingConfiguration
    {
        return $this->configuration;
    }

    /**
     * Get SupplierMatcher service reference
     *
     * @return SupplierMatcher The underlying matcher service
     * @since 7.6
     */
    public function getMatcher(): SupplierMatcher
    {
        return $this->matcher;
    }
}

/**
 * Vendor Candidate Adapter
 *
 * Minimal implementation that provides the interface expected by
 * ScoringRuleEngine without requiring a full KeywordMatch object.
 *
 * @package Ksfraser\FaBankImport\Services
 * @since   7.6
 */
class VendorCandidate implements SupplierCandidate
{
    /**
     * Supplier ID
     *
     * @var int
     */
    private int $partnerId;

    /**
     * Supplier name
     *
     * @var string
     */
    private string $partnerName;

    /**
     * Bank account short name
     *
     * @var string
     */
    private string $partnerAccount;

    /**
     * Constructor
     *
     * @param int $partnerId Supplier ID
     * @param string $partnerName Supplier name
     * @param string $partnerAccount Bank account short form
     */
    public function __construct(int $partnerId, string $partnerName, string $partnerAccount)
    {
        $this->partnerId = $partnerId;
        $this->partnerName = $partnerName;
        $this->partnerAccount = $partnerAccount;
    }

    /**
     * Get partner/supplier ID
     *
     * @return int
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * Get partner/supplier name
     *
     * @return string
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * Get partner type (1 = Supplier)
     *
     * @return int
     */
    public function getPartnerType(): int
    {
        return 1;
    }

    /**
     * Get bank account for matching
     *
     * @return string
     */
    public function getPartnerAccount(): string
    {
        return $this->partnerAccount;
    }
}

