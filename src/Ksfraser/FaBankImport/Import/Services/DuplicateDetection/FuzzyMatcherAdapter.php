<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Adapter for FuzzyMatcher to DuplicateMatcher interface
 *
 * Wraps the existing array-based FuzzyMatcher and implements the new
 * DuplicateMatcher interface for use in the Chain of Responsibility pattern.
 *
 * Responsibility:
 * - Convert BiTransaction entities to array format
 * - Delegate to FuzzyMatcher
 * - Convert results to DuplicateMatchResult
 * - Provide Priority 20 (executes after direct code match)
 */
final class FuzzyMatcherAdapter implements DuplicateMatcher
{
    private FuzzyMatcher $matcher;
    private LoggerInterface $logger;

    public function __construct(
        FuzzyMatcher $matcher = null,
        LoggerInterface $logger = null
    ) {
        $this->matcher = $matcher ?? new FuzzyMatcher();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Match transactions using fuzzy comparison
     *
     * Matches on: date + amount (±$0.01) + merchant/memo
     *
     * @param BiTransaction $transaction New transaction to check
     * @param BiTransaction $existingTransaction Existing transaction to compare
     * @return DuplicateMatchResult Match result with confidence and action
     */
    public function match(
        BiTransaction $transaction,
        BiTransaction $existingTransaction
    ): DuplicateMatchResult {
        try {
            // Convert entity to array for existing matcher
            $transactionArray = $this->biTransactionToArray($transaction);

            // Query for fuzzy matches
            $matches = $this->matcher->find($transactionArray);

            if (empty($matches)) {
                // No fuzzy matches found
                return DuplicateMatchResult::noMatch();
            }

            // Found fuzzy matches - compute confidence
            // Confidence is based on how closely the first match aligns
            $firstMatch = $matches[0];
            $confidence = $this->computeConfidence($transaction, $firstMatch);

            $this->logger->debug('FuzzyMatcher found potential duplicates', [
                'transactionId' => $transaction->getId(),
                'matchCount' => count($matches),
                'topConfidence' => $confidence,
            ]);

            // Fuzzy matches require review (user whitelist decision)
            return DuplicateMatchResult::match(
                confidence: $confidence,
                details: [
                    'matchType' => 'fuzzy',
                    'matchCount' => count($matches),
                    'topMatchId' => $firstMatch['id'] ?? null,
                    'topMatchMerchant' => $firstMatch['merchant'] ?? null,
                    'topMatchAmount' => $firstMatch['transactionAmount'] ?? null,
                ],
                action: 'review'
            );
        } catch (TransactionFetchException $e) {
            $this->logger->error('FuzzyMatcher query failed', [
                'error' => $e->getMessage(),
                'transactionId' => $transaction->getId(),
            ]);

            return DuplicateMatchResult::noMatch();
        }
    }

    /**
     * Get matcher priority
     *
     * Priority 20 (after DirectCodeMatcher at 10) to execute only when
     * direct code match fails.
     *
     * @return int Priority (lower executes first)
     */
    public function getPriority(): int
    {
        return 20;
    }

    /**
     * Get matcher name for logging
     *
     * @return string
     */
    public function getName(): string
    {
        return 'FuzzyMatcher';
    }

    /**
     * Should this matcher process this transaction?
     *
     * Fuzzy matcher operates on all transactions.
     *
     * @param BiTransaction $transaction
     * @return bool
     */
    public function shouldProcess(BiTransaction $transaction): bool
    {
        return true;
    }

    /**
     * Convert BiTransaction entity to array format for existing matcher
     *
     * @param BiTransaction $transaction
     * @return array
     */
    private function biTransactionToArray(BiTransaction $transaction): array
    {
        return [
            'valueTimestamp' => $transaction->getValueTimestamp()
                ? $transaction->getValueTimestamp()->format('Y-m-d')
                : null,
            'transactionAmount' => $transaction->getTransactionAmount(),
            'merchant' => $transaction->getMerchant(),
            'memo' => $transaction->getMemo(),
            'accountName' => $transaction->getAccountName(),
            'acctid' => $transaction->getAcctId(),
        ];
    }

    /**
     * Compute confidence score for fuzzy match
     *
     * Confidence based on field alignment:
     * - Exact merchant match: +0.4
     * - Exact amount match: +0.4
     * - Exact date match: +0.2
     *
     * @param BiTransaction $newTransaction
     * @param array $existingTransaction
     * @return float Confidence 0.0-1.0
     */
    private function computeConfidence(
        BiTransaction $newTransaction,
        array $existingTransaction
    ): float {
        $confidence = 0.0;

        // Check merchant match
        if ($newTransaction->getMerchant() === ($existingTransaction['merchant'] ?? null)) {
            $confidence += 0.4;
        }

        // Check amount match (should be ±$0.01)
        $amountDiff = abs(
            $newTransaction->getTransactionAmount() - ($existingTransaction['transactionAmount'] ?? 0)
        );
        if ($amountDiff < 0.01) {
            $confidence += 0.4;
        } else {
            $confidence += max(0, 0.4 - ($amountDiff * 0.1)); // Slight penalty for amount variance
        }

        // Check date match
        $newDate = $newTransaction->getValueTimestamp();
        $existingDate = isset($existingTransaction['valueTimestamp'])
            ? \DateTime::createFromFormat('Y-m-d', $existingTransaction['valueTimestamp'])
            : null;

        if ($newDate && $existingDate && $newDate->format('Y-m-d') === $existingDate->format('Y-m-d')) {
            $confidence += 0.2;
        }

        return min(1.0, max(0.0, $confidence));
    }
}
