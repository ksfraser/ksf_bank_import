<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Orchestrates duplicate detection using Chain of Responsibility pattern
 *
 * Processes matchers in priority order, continuing until:
 * - A high-confidence match is found (confidence >= threshold)
 * - All matchers complete
 * - A matcher returns a terminal result
 *
 * Replaces hardcoded Level 1/2/3 with dynamic, configurable matcher chain.
 * Benefits:
 * - Add matchers without modifying this class
 * - Customize order via priority
 * - Skip matchers conditionally
 * - Reuse matchers across services
 */
final class DuplicateDetectionChain
{
    /**
     * Matchers to process in order
     *
     * @var array<DuplicateMatcher>
     */
    private array $matchers = [];

    /**
     * Confidence threshold for immediate match result
     *
     * @var float
     */
    private float $confidenceThreshold;

    /**
     * PSR-3 logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Create duplicate detection chain
     *
     * @param array<DuplicateMatcher> $matchers Matchers to use (will be sorted by priority)
     * @param float $confidenceThreshold Minimum confidence for match (default 0.8)
     * @param LoggerInterface $logger Optional logger
     */
    public function __construct(
        array $matchers = [],
        float $confidenceThreshold = 0.8,
        ?LoggerInterface $logger = null
    ) {
        // Sort matchers by priority (ascending: lower = first)
        usort($matchers, fn($a, $b) => $a->getPriority() <=> $b->getPriority());

        $this->matchers = $matchers;
        $this->confidenceThreshold = max(0.0, min(1.0, $confidenceThreshold));
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Detect duplicates using matcher chain
     *
     * Processes matchers in priority order, collecting results until:
     * - Match found with confidence >= threshold (returns immediately)
     * - All matchers complete (returns best result)
     *
     * @param BiTransaction $transaction Transaction to check
     * @param BiTransaction $existingTransaction Existing transaction to compare
     * @return DuplicateMatchResult Best result from chain
     */
    public function detect(
        BiTransaction $transaction,
        BiTransaction $existingTransaction
    ): DuplicateMatchResult {
        $results = [];
        $bestResult = DuplicateMatchResult::noMatch();

        foreach ($this->matchers as $matcher) {
            // Skip if matcher doesn't apply to this transaction
            if (!$matcher->shouldProcess($transaction)) {
                $this->logger->debug(
                    sprintf(
                        'Skipping %s: transaction filter',
                        $matcher->getName()
                    )
                );
                continue;
            }

            try {
                $result = $matcher->match($transaction, $existingTransaction);
                $results[] = [
                    'matcher' => $matcher->getName(),
                    'priority' => $matcher->getPriority(),
                    'confidence' => $result->getConfidence(),
                    'isMatch' => $result->isMatch()
                ];

                $this->logger->debug(
                    sprintf(
                        '%s: confidence=%.2f, match=%s',
                        $matcher->getName(),
                        $result->getConfidence(),
                        $result->isMatch() ? 'yes' : 'no'
                    )
                );

                // Update best result if this is better
                if ($result->getConfidence() > $bestResult->getConfidence()) {
                    $bestResult = $result;
                }

                // Stop if high-confidence match found
                if ($result->isHighConfidence($this->confidenceThreshold)) {
                    $this->logger->info(
                        sprintf(
                            'Duplicate match found by %s (confidence=%.2f)',
                            $matcher->getName(),
                            $result->getConfidence()
                        )
                    );
                    return $result;
                }
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf(
                        '%s failed: %s',
                        $matcher->getName(),
                        $e->getMessage()
                    )
                );
                continue;
            }
        }

        // No match found after all matchers
        $this->logger->debug(
            sprintf(
                'No duplicate match. Checked %d matchers',
                count($results)
            )
        );

        return $bestResult;
    }

    /**
     * Add matcher to chain
     *
     * Useful for runtime chain configuration.
     * Chain is re-sorted by priority after adding.
     *
     * @param DuplicateMatcher $matcher Matcher to add
     * @return self For fluent interface
     */
    public function addMatcher(DuplicateMatcher $matcher): self
    {
        $this->matchers[] = $matcher;

        // Re-sort by priority
        usort($this->matchers, fn($a, $b) => $a->getPriority() <=> $b->getPriority());

        return $this;
    }

    /**
     * Get matchers in execution order
     *
     * @return array<DuplicateMatcher> Matchers sorted by priority
     */
    public function getMatchers(): array
    {
        return $this->matchers;
    }

    /**
     * Get confidence threshold
     *
     * @return float Minimum confidence for match
     */
    public function getConfidenceThreshold(): float
    {
        return $this->confidenceThreshold;
    }

    /**
     * Set confidence threshold
     *
     * @param float $threshold New threshold (0.0-1.0)
     * @return self For fluent interface
     */
    public function setConfidenceThreshold(float $threshold): self
    {
        $this->confidenceThreshold = max(0.0, min(1.0, $threshold));
        return $this;
    }
}
