<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Adapter for DirectCodeMatcher to DuplicateMatcher interface
 *
 * Wraps the existing array-based DirectCodeMatcher and implements the new
 * DuplicateMatcher interface for use in the Chain of Responsibility pattern.
 *
 * Responsibility:
 * - Convert BiTransaction entities to array format
 * - Delegate to DirectCodeMatcher
 * - Convert results to DuplicateMatchResult
 * - Provide Priority 10 (executes early, before fuzzy matching)
 */
final class DirectCodeMatcherAdapter implements DuplicateMatcher
{
    private DirectCodeMatcher $matcher;
    private LoggerInterface $logger;

    public function __construct(
        DirectCodeMatcher $matcher = null,
        LoggerInterface $logger = null
    ) {
        $this->matcher = $matcher ?? new DirectCodeMatcher();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Match transactions using direct code comparison
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
            // Convert entities to arrays for existing matcher
            $transactionArray = $this->biTransactionToArray($transaction);

            // Use findAndCompare to get field mismatch details
            $result = $this->matcher->findAndCompare($transactionArray);

            if ($result === null) {
                // No direct code match found
                return DuplicateMatchResult::noMatch();
            }

            // Code matched - check if fields also matched
            $fieldsThatDiffer = $result['fields_that_differ'] ?? '';
            $allFieldsMatch = empty($fieldsThatDiffer);

            $this->logger->debug('DirectCodeMatcher detected code match', [
                'transactionId' => $transaction->getId(),
                'existingId' => $existingTransaction->getId(),
                'allFieldsMatch' => $allFieldsMatch,
                'fieldsThatDiffer' => $fieldsThatDiffer,
            ]);

            // Exact code match = 100% confidence, but action depends on fields
            $action = $allFieldsMatch ? 'skip' : 'review';
            $confidence = 1.0;

            return DuplicateMatchResult::match(
                confidence: $confidence,
                details: [
                    'matchType' => 'directCode',
                    'existingTransactionId' => $existingTransaction->getId(),
                    'fieldsThatDiffer' => $fieldsThatDiffer,
                    'allFieldsMatch' => $allFieldsMatch,
                ],
                action: $action
            );
        } catch (TransactionFetchException $e) {
            $this->logger->error('DirectCodeMatcher query failed', [
                'error' => $e->getMessage(),
                'transactionId' => $transaction->getId(),
            ]);

            return DuplicateMatchResult::noMatch();
        }
    }

    /**
     * Get matcher priority
     * 
     * Intentionally low priority (10) to execute before fuzzy matching.
     * Direct code matches are definitive and consume significant resources.
     *
     * @return int Priority (lower executes first)
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * Get matcher name for logging
     *
     * @return string
     */
    public function getName(): string
    {
        return 'DirectCodeMatcher';
    }

    /**
     * Should this matcher process this transaction?
     *
     * Direct code matcher operates on all transactions.
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
            'transactionCode' => $transaction->getTransactionCode(),
            'acctid' => $transaction->getAcctId(),
            'valueTimestamp' => $transaction->getValueTimestamp()
                ? $transaction->getValueTimestamp()->format('Y-m-d H:i:s')
                : null,
            'transactionAmount' => $transaction->getTransactionAmount(),
            'merchant' => $transaction->getMerchant(),
            'memo' => $transaction->getMemo(),
            'reference' => $transaction->getTransactionTitle(),
        ];
    }
}
