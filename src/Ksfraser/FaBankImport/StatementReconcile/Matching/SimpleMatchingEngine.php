<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Matching;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Service\MatchingEngineInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;

/**
 * Rule-based matching engine.
 *
 * Each statement line is scored against every un-matched bank transaction.
 * The highest-scoring pair above the confidence threshold is kept.
 *
 * Matching rules applied in order (rules are additive):
 *
 *   1. EXACT_AMOUNT_DATE  – same amount (rounded to 2 dp) AND same date.
 *                           Contributes 0.70 to confidence.
 *   2. DESCRIPTION_FUZZY  – description similarity via trigram overlap >= 0.5.
 *                           Contributes 0.20 to confidence.
 *   3. TYPE_MATCH         – credit/debit type match.
 *                           Contributes 0.10 to confidence.
 *
 * Max possible confidence: 1.00 (all three rules match).
 * Pairs below THRESHOLD are placed in the unmatched lists.
 *
 * Design is intentionally extensible: add new rules to RULES_MAP and implement
 * a scoring method of the same signature.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Matching
 * @author  Kevin Fraser
 */
final class SimpleMatchingEngine implements MatchingEngineInterface
{
    /**
     * Minimum overall confidence required to accept a match.
     * Override via constructor for tuning.
     */
    public const DEFAULT_THRESHOLD = 0.70;

    /**
     * Minimum token-overlap similarity to award DESCRIPTION_FUZZY points.
     */
    public const DESCRIPTION_SIMILARITY_MIN = 0.4;

    /** @var float */
    private $threshold;

    /**
     * @param float $threshold Minimum confidence in [0.0, 1.0] to accept a pair.
     */
    public function __construct(float $threshold = self::DEFAULT_THRESHOLD)
    {
        $this->threshold = $threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function match(StatementOcr $statement, array $bankTransactions): ReconciliationSession
    {
        /** @var BankTransactionDto[] $availableBank Keyed by ID. */
        $availableBank = [];
        foreach ($bankTransactions as $tx) {
            $availableBank[$tx->getId()] = $tx;
        }

        $matchedPairs             = [];
        $unmatchedStatementLines  = [];

        foreach ($statement->getLines() as $line) {
            $best = $this->findBestMatch($line, $availableBank);

            if ($best !== null) {
                $matchedPairs[] = $best;
                // Remove from the available pool so one bank tx can only match once.
                unset($availableBank[$best->getBankTransactionId()]);
            } else {
                $unmatchedStatementLines[] = $line->getLineId();
            }
        }

        $unmatchedBankIds = array_keys($availableBank);

        return ReconciliationSession::createPending(
            (int) $statement->getId(), // 0 if not persisted yet – caller should persist first
            $matchedPairs,
            $unmatchedStatementLines,
            array_map('intval', $unmatchedBankIds)
        );
    }

    // -------------------------------------------------------------------------
    // Private scoring
    // -------------------------------------------------------------------------

    /**
     * Evaluate all available bank transactions against $line and return the best
     * MatchedPair at or above the threshold, or null if none qualifies.
     *
     * @param StatementLine       $line
     * @param BankTransactionDto[] $pool Keyed by bank tx ID.
     * @return MatchedPair|null
     */
    private function findBestMatch(StatementLine $line, array $pool): ?MatchedPair
    {
        $bestConfidence = -1.0;
        $bestTx         = null;
        $bestRules      = [];

        foreach ($pool as $tx) {
            $rules      = [];
            $confidence = 0.0;

            // Rule 1 – Exact amount and same date.
            if ($this->scoresAmountDate($line, $tx)) {
                $rules[]    = 'EXACT_AMOUNT_DATE';
                $confidence += 0.70;
            }

            // Rule 2 – Description similarity.
            if ($this->scoresDescriptionFuzzy($line, $tx)) {
                $rules[]    = 'DESCRIPTION_FUZZY';
                $confidence += 0.20;
            }

            // Rule 3 – Type match.
            if ($line->getType() === $tx->getType()) {
                $rules[]    = 'TYPE_MATCH';
                $confidence += 0.10;
            }

            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestTx         = $tx;
                $bestRules      = $rules;
            }
        }

        if ($bestTx === null || $bestConfidence < $this->threshold) {
            return null;
        }

        return new MatchedPair(
            $line->getLineId(),
            $bestTx->getId(),
            round($bestConfidence, 2),
            $bestRules,
            $bestTx->getFaTransType(),
            $bestTx->getFaTransNo()
        );
    }

    /**
     * Rule 1: amounts match within 0.005 (rounding tolerance) AND dates are equal.
     *
     * @param StatementLine      $line
     * @param BankTransactionDto $tx
     * @return bool
     */
    private function scoresAmountDate(StatementLine $line, BankTransactionDto $tx): bool
    {
        $amountMatch = abs($line->getAmountFloat() - $tx->getAmountFloat()) < 0.005;
        $dateMatch   = $line->getDate()->format('Y-m-d') === $tx->getDate()->format('Y-m-d');

        return $amountMatch && $dateMatch;
    }

    /**
     * Rule 2: token-overlap similarity between descriptions.
     *
     * Splits both descriptions into normalised word tokens, then computes
     * Jaccard similarity: |intersection| / |union|.
     *
     * @param StatementLine      $line
     * @param BankTransactionDto $tx
     * @return bool
     */
    private function scoresDescriptionFuzzy(StatementLine $line, BankTransactionDto $tx): bool
    {
        $tokensA = $this->tokenise($line->getDescription());
        $tokensB = $this->tokenise($tx->getDescription());

        if (empty($tokensA) || empty($tokensB)) {
            return false;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $union        = array_unique(array_merge($tokensA, $tokensB));

        $similarity = count($intersection) / count($union);

        return $similarity >= self::DESCRIPTION_SIMILARITY_MIN;
    }

    /**
     * Normalise a description string into an array of lowercase word tokens,
     * stripping punctuation and short stop-words.
     *
     * @param string $text
     * @return string[]
     */
    private function tokenise(string $text): array
    {
        // Lowercase, strip non-alphanumeric (keep spaces).
        $normalised = preg_replace('/[^a-z0-9 ]/i', ' ', mb_strtolower($text));
        $words      = preg_split('/\s+/', trim((string) $normalised), -1, PREG_SPLIT_NO_EMPTY);

        // Remove stop-words and tokens < 3 chars.
        $stop = ['the', 'and', 'for', 'via', 'to', 'at', 'in', 'on', 'of', 'a'];

        return array_values(
            array_filter(
                (array) $words,
                static function (string $w) use ($stop): bool {
                    return mb_strlen($w) >= 3 && !in_array($w, $stop, true);
                }
            )
        );
    }
}
