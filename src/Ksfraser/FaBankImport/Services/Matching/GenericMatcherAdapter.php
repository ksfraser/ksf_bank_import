<?php

/**
 * Generic Matcher Adapter
 *
 * Adapts ksf_FA_Common's shared GenericMatcher (ksfraser\FrontAccounting\Common\Matching)
 * to bank-import transaction/partner scoring. Normalizes bank-import transaction
 * and candidate rows into the canonical field names the shared engine expects,
 * and converts 0.0-1.0 confidences into the 0-100 percentage scale used by
 * TransactionPartnerMatcher.
 *
 * @package    Ksfraser\FaBankImport\Services\Matching
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-11-17)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Matching;

use ksfraser\FrontAccounting\Common\Matching\MatchingConfig;
use ksfraser\FrontAccounting\Common\Matching\Matcher;

/**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
 *
 * Adapter: bank-import rows -> shared GenericMatcher -> percentage scores.
 *
 * Scoring factors (configurable via MatchingConfig weights): name (memo vs
 * partner name), amount, date, reference. Factors whose field is missing on
 * either side are skipped and their weight renormalized by the shared engine.
 *
 * @since 7.6
 */
class GenericMatcherAdapter
{
    /**
     * Shared matcher from ksf_FA_Common
     *
     * @var Matcher
     */
    private $matcher;

    /**
     * Confidence >= this is an auto-approve candidate (shared config)
     *
     * @var float
     */
    private $autoApproveThreshold;

    /**
     * Confidence >= this needs review rather than rejection (shared config)
     *
     * @var float
     */
    private $reviewThreshold;

    /**
     * Constructor
     *
     * @param MatchingConfig|null $config Optional shared matching config override
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    public function __construct(MatchingConfig $config = null)
    {
        $this->matcher = new Matcher(
            $config !== null ? $config : $this->defaultConfig()
        );
        $cfg = $this->matcher->getConfig();
        $this->autoApproveThreshold = $cfg->getAutoApproveThreshold();
        $this->reviewThreshold = $cfg->getReviewThreshold();
    }

    /**
     * Score bank-import candidates against a transaction.
     *
     * @param array $transaction Bank transaction (memo/description, amount,
     *                           trans_date) or a shared-engine staged record
     * @param array $candidates  Candidate rows keyed by their natural columns:
     *                           - partners: partner_id, name, account
     *                           - bank accounts: bank_account_id, bank_account_name, account_number
     * @return array Scored candidates sorted by confidence descending:
     *               [ ['row' => array, 'id' => int, 'name' => string,
     *                  'confidence' => float 0-1, 'score' => float 0-100,
     *                  'field_scores' => array, 'match_type' => string], ... ]
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    public function scoreCandidates(array $transaction, array $candidates): array
    {
        if (empty($candidates)) {
            return [];
        }

        $staged = $this->normalizeTransaction($transaction);
        $normalized = [];
        $identity = [];

        foreach (array_values($candidates) as $i => $row) {
            [$candidate, $id, $name] = $this->normalizeCandidate($row);
            $normalized[] = $candidate;
            $identity[$i] = ['row' => $row, 'id' => $id, 'name' => $name];
        }

        $result = $this->matcher->match($staged, $normalized);

        $scored = [];
        foreach ($result->getCandidates() as $match) {
            $i = array_search($match->getRecord(), $normalized, true);
            if ($i === false) {
                continue;
            }
            $confidence = $match->getConfidence();
            $scored[] = [
                'row'          => $identity[$i]['row'],
                'id'           => $identity[$i]['id'],
                'name'         => $identity[$i]['name'],
                'confidence'   => $confidence,
                'score'        => round($confidence * 100.0, 4),
                'field_scores' => $match->getFieldScores(),
                'match_type'   => $match->getMatchType(),
            ];
        }

        return $scored;
    }

    /**
     * Whether a confidence warrants auto-approval under the shared config.
     *
     * @param float $confidence 0.0-1.0
     * @return bool
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    public function shouldAutoApprove(float $confidence): bool
    {
        return $this->autoApproveThreshold !== null && $confidence >= $this->autoApproveThreshold;
    }

    /**
     * Whether a confidence warrants review under the shared config.
     *
     * @param float $confidence 0.0-1.0
     * @return bool
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    public function needsReview(float $confidence): bool
    {
        return $this->reviewThreshold !== null && $confidence >= $this->reviewThreshold;
    }

    /**
     * Bank-import default MatchingConfig for the shared engine.
     *
     * Name (memo similarity) dominates; amount and date are secondary signals.
     * Transaction candidates carry no stored amount/date/reference, so those
     * factors stay enabled but only contribute when a candidate row supplies
     * the field (the shared engine renormalizes weights).
     *
     * @return MatchingConfig
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function defaultConfig(): MatchingConfig
    {
        return new MatchingConfig([
            'field_map' => [
                'person_id' => 'person_id',
                'email'     => 'email',
                'amount'    => 'amount',
                'date'      => 'date',
                'name'      => 'name',
                'phone'     => 'phone',
                'reference' => 'reference',
            ],
            'weights' => [
                'person_id' => 0.0,
                'email'     => 0.0,
                'amount'    => 0.10,
                'date'      => 0.10,
                'name'      => 0.70,
                'phone'     => 0.0,
                'reference' => 0.10,
            ],
            'auto_approve'        => 0.95,
            'review'              => 0.80,
            'amount_tolerance'    => 0.01,
            'date_tolerance_days' => 1,
            'name_soundex'        => true,
        ]);
    }

    /**
     * Normalize a bank transaction into the shared engine's staged shape.
     *
     * @param array $transaction
     * @return array Staged record with canonical field names
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function normalizeTransaction(array $transaction): array
    {
        $staged = [];

        $memo = $transaction['memo'] ?? $transaction['description'] ?? null;
        if ($memo !== null && (string)$memo !== '') {
            $staged['name'] = (string)$memo;
        }

        if (isset($transaction['amount'])) {
            $staged['amount'] = (float)$transaction['amount'];
        }

        $date = $transaction['trans_date'] ?? $transaction['date'] ?? null;
        if ($date !== null && (string)$date !== '') {
            $staged['date'] = (string)$date;
        }

        if (isset($transaction['reference']) && (string)$transaction['reference'] !== '') {
            $staged['reference'] = (string)$transaction['reference'];
        }

        return $staged;
    }

    /**
     * Normalize a candidate row (partner or bank account) into shared shape.
     *
     * @param array $row
     * @return array [normalizedCandidate, id, name]
     * @since 7.6
          * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     */
    private function normalizeCandidate(array $row): array
    {
        $candidate = [];

        $id = (int)($row['partner_id'] ?? $row['bank_account_id'] ?? 0);
        $name = (string)($row['name'] ?? $row['bank_account_name'] ?? '');

        if ($name !== '') {
            $candidate['name'] = $name;
        }

        // Candidate-side optional signals, when the row supplies them
        if (isset($row['amount']) && $row['amount'] !== '') {
            $candidate['amount'] = (float)$row['amount'];
        }
        if (!empty($row['trans_date'])) {
            $candidate['date'] = (string)$row['trans_date'];
        }
        if (!empty($row['reference'])) {
            $candidate['reference'] = (string)$row['reference'];
        }

        return [$candidate, $id, $name];
    }
}
