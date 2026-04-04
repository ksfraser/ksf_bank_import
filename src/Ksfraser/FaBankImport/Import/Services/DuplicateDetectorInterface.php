<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\DuplicateDetectedException;

/**
 * Contract for detecting duplicate transactions
 *
 * Identifies potential duplicate transactions using various strategies:
 * - Exact duplicates (same amount, date, reference)
 * - Probable duplicates (similar amount, nearby date)
 * - Intelligent matching based on fuzzy logic
 */
interface DuplicateDetectorInterface
{
    /**
     * Detect duplicates for a transaction
     *
     * @param array<string, mixed> $transaction Transaction data to check
     * @return array<int, array<string, mixed>> Array of matching duplicates
     *
     * @throws DuplicateDetectedException If exact duplicate found
     */
    public function detectDuplicates(array $transaction): array;

    /**
     * Get detector name
     *
     * @return string
     */
    public function getName(): string;
}
