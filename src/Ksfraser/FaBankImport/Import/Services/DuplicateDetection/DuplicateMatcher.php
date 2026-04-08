<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Strategy interface for duplicate detection matchers
 *
 * Enables Chain of Responsibility pattern with pluggable matchers.
 * Each matcher checks for duplicates using a specific strategy:
 * - DirectCodeMatcher: Exact transaction code match
 * - FuzzyMatcher: Fuzzy comparison of transaction details
 * - RulesBasedMatcher: Custom rules and whitelists
 *
 * Matchers are composed in a chain, processed in order until a match is found
 * or all matchers complete.
 */
interface DuplicateMatcher
{
    /**
     * Check for duplicates using this matcher's strategy
     *
     * @param BiTransaction $transaction Transaction to check
     * @param BiTransaction $existingTransaction Existing transaction to compare against
     * @return DuplicateMatchResult Result with match status, confidence, and action
     */
    public function match(
        BiTransaction $transaction,
        BiTransaction $existingTransaction
    ): DuplicateMatchResult;

    /**
     * Get matcher priority/order
     *
     * Lower numbers execute first in the chain.
     * Allows ordering without hardcoding levels.
     *
     * @return int Priority (0 = first, 100+ = later)
     */
    public function getPriority(): int;

    /**
     * Get matcher name for logging
     *
     * @return string Descriptive name of this matcher
     */
    public function getName(): string;

    /**
     * Check if this matcher should process the transaction
     *
     * Optional filter to skip this matcher for certain transactions.
     * Useful for: Transaction type filtering, date range checking, etc.
     *
     * @param BiTransaction $transaction Transaction to check
     * @return bool True if matcher should process
     */
    public function shouldProcess(BiTransaction $transaction): bool;
}
