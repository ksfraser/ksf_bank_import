<?php

/**
 * Transaction Partner Matcher
 *
 * Unified matching engine that scores incoming transactions against all
 * available partners (suppliers, customers, bank accounts) regardless of
 * transaction type, then recommends partner type based on match confidence.
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;

/**
 * Transaction Partner Matcher
 *
 * Orchestrates matching of incoming bank transactions against all available
 * partners (suppliers, customers, bank accounts) in a single unified pass.
 * Returns ranked matches allowing UI to:
 * - Pre-select best matching partner
 * - Display partner type recommendation
 * - Show confidence score
 * - Allow user override
 *
 * @since 7.6
 */
class TransactionPartnerMatcher
{
    /**
     * Scoring engine for calculating match scores
     *
     * @var ScoringRuleEngine
     */
    private ScoringRuleEngine $engine;

    /**
     * Matching configuration (supplier or customer optimized)
     *
     * @var object Configuration with getMinimumConfidenceThreshold() method
     */
    private object $config;

    /**
     * Partner type constants
     *
     * @var int
     */
    private int $partnerTypeSupplier = 1;    // PT_SUPPLIER
    private int $partnerTypeCustomer = 2;    // PT_CUSTOMER
    private int $partnerTypeBankTransfer = 4; // ST_BANKTRANSFER

    /**
     * Constructor
     *
     * @param ScoringRuleEngine $engine Scoring engine instance
     * @param object            $config Matching configuration (SupplierMatchingConfiguration or CustomerMatchingConfiguration)
     */
    public function __construct(
        ScoringRuleEngine $engine,
        object $config
    ) {
        $this->engine = $engine;
        $this->config = $config;
    }

    /**
     * Match a transaction against all available partners
     *
     * Scores the transaction against suppliers, customers, and bank transfer
     * accounts in a single pass. Returns combined results ranked by confidence.
     *
     * @param array $transaction The transaction data to match:
     *                           - account: Our bank account
     *                           - partner_account: Their bank account
     *                           - amount: Transaction amount
     *                           - memo: Transaction description/memo
     *                           - is_invoice: Whether this is an invoice type
     *                           - type: FA transaction type code
     * @param array $suppliers   Array of supplier candidates with keys:
     *                           - partner_id, name, account
     * @param array $customers   Array of customer candidates with keys:
     *                           - partner_id, name, account
     * @param array $bankAccounts Array of bank account candidates with keys:
     *                            - bank_account_id, bank_account_name, account_number
     *
     * @return array Ranked match results:
     *               [
     *                 'supplier' => [TransactionMatchResult, ...],
     *                 'customer' => [TransactionMatchResult, ...],
     *                 'bank_transfer' => [TransactionMatchResult, ...],
     *                 'best_match' => TransactionMatchResult (highest confidence overall)
     *               ]
     */
    public function matchTransaction(
        array $transaction,
        array $suppliers = [],
        array $customers = [],
        array $bankAccounts = []
    ): array {
        $results = [
            'supplier' => [],
            'customer' => [],
            'bank_transfer' => [],
            'best_match' => null,
        ];

        $maxScore = -999;
        $bestResult = null;

        // Score against suppliers
        foreach ($suppliers as $supplier) {
            $candidate = new VendorCandidate(
                (int)$supplier['partner_id'],
                $supplier['name'],
                $supplier['account'] ?? '',
                $this->partnerTypeSupplier
            );

            $score = $this->engine->calculateAdjustment($transaction, $candidate);

            if ($score > 0) {
                $result = new TransactionMatchResult(
                    (int)$supplier['partner_id'],
                    $supplier['name'],
                    $score,
                    'supplier'
                );
                $results['supplier'][] = $result;

                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestResult = $result;
                }
            }
        }

        // Score against customers
        foreach ($customers as $customer) {
            $candidate = new VendorCandidate(
                (int)$customer['partner_id'],
                $customer['name'],
                $customer['account'] ?? '',
                $this->partnerTypeCustomer
            );

            $score = $this->engine->calculateAdjustment($transaction, $candidate);

            if ($score > 0) {
                $result = new TransactionMatchResult(
                    (int)$customer['partner_id'],
                    $customer['name'],
                    $score,
                    'customer'
                );
                $results['customer'][] = $result;

                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestResult = $result;
                }
            }
        }

        // Score against bank transfer accounts
        foreach ($bankAccounts as $account) {
            $candidate = new VendorCandidate(
                (int)$account['bank_account_id'],
                $account['bank_account_name'],
                $account['account_number'],
                $this->partnerTypeBankTransfer
            );

            $score = $this->engine->calculateAdjustment($transaction, $candidate);

            if ($score > 0) {
                $result = new TransactionMatchResult(
                    (int)$account['bank_account_id'],
                    $account['bank_account_name'],
                    $score,
                    'bank_transfer'
                );
                $results['bank_transfer'][] = $result;

                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestResult = $result;
                }
            }
        }

        // Sort each category by score descending
        foreach ($results as &$category) {
            if (is_array($category) && count($category) > 0) {
                usort($category, function($a, $b) {
                    return $b->getScore() <=> $a->getScore();
                });
            }
        }

        // Set best match if it meets minimum threshold
        if ($bestResult !== null && $bestResult->meetsThreshold($this->config->getMinimumConfidenceThreshold())) {
            $results['best_match'] = $bestResult;
        }

        return $results;
    }

    /**
     * Set partner type constants (for testability)
     *
     * @param int $supplier     PT_SUPPLIER constant value
     * @param int $customer     PT_CUSTOMER constant value
     * @param int $bankTransfer ST_BANKTRANSFER constant value
     * @return void
     */
    public function setPartnerTypeConstants(int $supplier, int $customer, int $bankTransfer): void
    {
        $this->partnerTypeSupplier = $supplier;
        $this->partnerTypeCustomer = $customer;
        $this->partnerTypeBankTransfer = $bankTransfer;
    }
}
