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

/**

 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Matching\GenericMatcherAdapter;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;

/**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006

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
     * Optional shared-engine adapter (ksf_FA_Common GenericMatcher). When set,
     * candidate scoring delegates to the shared engine via the adapter instead
     * of the local ScoringRuleEngine.
     *
     * @var GenericMatcherAdapter|null
     */
    private ?GenericMatcherAdapter $genericMatcher;

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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Constructor
     *
     * @param ScoringRuleEngine $engine Scoring engine instance
     * @param object            $config Matching configuration (SupplierMatchingConfiguration or CustomerMatchingConfiguration)
     * @param GenericMatcherAdapter|null $genericMatcher Optional shared GenericMatcher
     *        adapter (ksf_FA_Common); when provided it replaces ScoringRuleEngine
     *        as the candidate scoring backend
     */
    public function __construct(
        ScoringRuleEngine $engine,
        object $config,
        ?GenericMatcherAdapter $genericMatcher = null
    ) {
        $this->engine = $engine;
        $this->config = $config;
        $this->genericMatcher = $genericMatcher;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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
        $results['supplier'] = $this->scoreCandidateSet(
            $transaction,
            $suppliers,
            'partner_id',
            'name',
            'account',
            'supplier',
            $this->partnerTypeSupplier
        );

        // Score against customers
        $results['customer'] = $this->scoreCandidateSet(
            $transaction,
            $customers,
            'partner_id',
            'name',
            'account',
            'customer',
            $this->partnerTypeCustomer
        );

        // Score against bank transfer accounts
        $results['bank_transfer'] = $this->scoreCandidateSet(
            $transaction,
            $bankAccounts,
            'bank_account_id',
            'bank_account_name',
            'account_number',
            'bank_transfer',
            $this->partnerTypeBankTransfer
        );

        foreach ($results['supplier'] as $result) {
            if ($result->getScore() > $maxScore) {
                $maxScore = $result->getScore();
                $bestResult = $result;
            }
        }
        foreach ($results['customer'] as $result) {
            if ($result->getScore() > $maxScore) {
                $maxScore = $result->getScore();
                $bestResult = $result;
            }
        }
        foreach ($results['bank_transfer'] as $result) {
            if ($result->getScore() > $maxScore) {
                $maxScore = $result->getScore();
                $bestResult = $result;
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

        // For e-transfer memos, bias selection to the type with stronger memo consistency
        // between customer payments and supplier refunds.
        if ($this->isEtransferMemo($transaction)) {
            $hintPreferredBest = $this->selectHintPreferredBest(
                $transaction,
                $results['customer'],
                $results['supplier']
            );
            if ($hintPreferredBest !== null) {
                $bestResult = $hintPreferredBest;
            } else {
            $typePreferredBest = $this->selectTypePreferredBest(
                $results['customer'],
                $results['supplier']
            );
            if ($typePreferredBest !== null) {
                $bestResult = $typePreferredBest;
            }
            }
        }

        // Set best match if it meets minimum threshold
        if ($bestResult !== null && $bestResult->meetsThreshold($this->config->getMinimumConfidenceThreshold())) {
            $results['best_match'] = $bestResult;
        }

        return $results;
    }

    /**
     * Score one candidate bucket against the transaction.
     *
     * When the shared GenericMatcher adapter is injected the bucket is scored
     * through it (name/amount/date/reference weighted confidence); otherwise
     * each candidate goes through the local ScoringRuleEngine. Candidates
     * scoring <= 0 are dropped; survivors are returned sorted by score
     * descending as TransactionMatchResult instances.
     *
     * @param array $transaction Bank transaction
     * @param array $candidates  Candidate rows for the bucket
     * @param string $idKey      Candidate row key holding the partner id
     * @param string $nameKey    Candidate row key holding the display name
     * @param string $accountKey Candidate row key holding the account number
     * @param string $type       Bucket label ('supplier'|'customer'|'bank_transfer')
     * @param int $partnerType   FA partner-type constant for the bucket
     * @return TransactionMatchResult[] sorted by score descending
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function scoreCandidateSet(
        array $transaction,
        array $candidates,
        string $idKey,
        string $nameKey,
        string $accountKey,
        string $type,
        int $partnerType
    ): array {
        if (empty($candidates)) {
            return [];
        }

        if ($this->genericMatcher !== null) {
            $scored = $this->genericMatcher->scoreCandidates($transaction, $candidates);
            $results = [];
            foreach ($scored as $entry) {
                if ($entry['score'] <= 0) {
                    continue;
                }
                $results[] = new TransactionMatchResult(
                    (int)$entry['id'],
                    (string)$entry['name'],
                    (float)$entry['score'],
                    $type
                );
            }
        } else {
            $results = [];
            foreach ($candidates as $row) {
                $candidate = new VendorCandidate(
                    (int)$row[$idKey],
                    (string)$row[$nameKey],
                    (string)($row[$accountKey] ?? ''),
                    $partnerType
                );

                $score = $this->engine->calculateAdjustment($transaction, $candidate);

                if ($score > 0) {
                    $results[] = new TransactionMatchResult(
                        (int)$row[$idKey],
                        (string)$row[$nameKey],
                        $score,
                        $type
                    );
                }
            }
        }

        usort($results, function ($a, $b) {
            return $b->getScore() <=> $a->getScore();
        });

        return $results;
    }

    /**
     * Set partner type constants (for testability)
     *
     * @param int $supplier     PT_SUPPLIER constant value
     * @param int $customer     PT_CUSTOMER constant value
     * @param int $bankTransfer ST_BANKTRANSFER constant value
     * @return void
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    public function setPartnerTypeConstants(int $supplier, int $customer, int $bankTransfer): void
    {
        $this->partnerTypeSupplier = $supplier;
        $this->partnerTypeCustomer = $customer;
        $this->partnerTypeBankTransfer = $bankTransfer;
    }

    /**
     * Detect whether transaction memo appears to be an e-transfer descriptor.
     *
     * @param array $transaction
     * @return bool
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function isEtransferMemo(array $transaction): bool
    {
        $memo = strtoupper((string)($transaction['memo'] ?? $transaction['description'] ?? ''));
        if ($memo === '') {
            return false;
        }

        return strpos($memo, 'E-TRANSFER') !== false
            || strpos($memo, 'ETRANSFER') !== false
            || strpos($memo, 'INTERAC') !== false;
    }

    /**
     * Choose best match from customer or supplier based on consistency score.
     *
     * Consistency is derived from the top-N confidence concentration per type,
     * so we prefer the type whose memo pattern is repeatedly stronger.
     *
     * @param array $customerMatches Array of TransactionMatchResult
     * @param array $supplierMatches Array of TransactionMatchResult
     * @return TransactionMatchResult|null
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function selectTypePreferredBest(array $customerMatches, array $supplierMatches): ?TransactionMatchResult
    {
        if (empty($customerMatches) && empty($supplierMatches)) {
            return null;
        }
        if (empty($customerMatches)) {
            return $supplierMatches[0] ?? null;
        }
        if (empty($supplierMatches)) {
            return $customerMatches[0] ?? null;
        }

        $customerConsistency = $this->calculateConsistencyScore($customerMatches);
        $supplierConsistency = $this->calculateConsistencyScore($supplierMatches);

        return $customerConsistency >= $supplierConsistency
            ? $customerMatches[0]
            : $supplierMatches[0];
    }

    /**
     * Prefer best match from historical memo-type hint if provided.
     *
     * @param array $transaction
     * @param array $customerMatches
     * @param array $supplierMatches
     * @return TransactionMatchResult|null
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function selectHintPreferredBest(
        array $transaction,
        array $customerMatches,
        array $supplierMatches
    ): ?TransactionMatchResult {
        $hint = strtolower((string)($transaction['memo_consistency_hint'] ?? ''));
        if ($hint === 'customer' && !empty($customerMatches)) {
            return $customerMatches[0];
        }
        if ($hint === 'supplier' && !empty($supplierMatches)) {
            return $supplierMatches[0];
        }

        return null;
    }

    /**
     * Calculate consistency score from top matches in a type bucket.
     *
     * @param array $matches Array of TransactionMatchResult
     * @return float
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function calculateConsistencyScore(array $matches): float
    {
        if (empty($matches)) {
            return 0.0;
        }

        $top = array_slice($matches, 0, 3);
        $sum = 0.0;
        $max = 0.0;

        foreach ($top as $match) {
            if (!$match instanceof TransactionMatchResult) {
                continue;
            }
            $score = $match->getScore();
            $sum += $score;
            if ($score > $max) {
                $max = $score;
            }
        }

        $count = count($top);
        if ($count === 0) {
            return 0.0;
        }

        $avg = $sum / $count;

        // For memo consistency we prioritize repeated support over a single spike.
        // This helps classify e-transfers by historical type consistency.
        return $sum + ($avg * 0.25) + ($count * 5.0) + ($max * 0.1);
    }
}
