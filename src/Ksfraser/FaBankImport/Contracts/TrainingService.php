<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Contracts;

/**
 * TrainingService Contract
 * 
 * Defines the interface for building training data from historical transactions.
 * Training data is used to improve partner matching accuracy by tracking
 * transaction patterns and occurrence counts for different partner types.
 * 
 * Implementers of this interface handle the core logic of:
 * - Scanning historical transactions across all partner types
 * - Finding matching transaction patterns
 * - Updating partner learning metrics (occurrence counts, timestamps)
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
interface TrainingService
{
    /**
     * Build training data from historical transactions
     * 
     * Scans all partners and their historical transactions to:
     * 1. Count transaction occurrences for each partner
     * 2. Update last matched timestamp
     * 3. Build learning metrics for improved matching
     * 
     * @param bool $dryRun If true, simulates the operation without persisting changes
     * @return array<string, int> Statistics about the training:
     *   - 'processed': Number of transactions processed
     *   - 'learned': Number of partners with new learning data
     *   - 'skipped': Number of partners skipped (already optimal)
     * 
     * @throws PartnerException If partner data cannot be accessed
     * @throws TrainingException If the training operation fails
     */
    public function buildTrainingData(bool $dryRun = false): array;
}
