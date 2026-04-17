<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Contracts\TrainingService as TrainingServiceInterface;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * TrainingService - Build training data from historical partner transactions
 *
 * Processes historical transactions and updates partner learning data
 * (occurrence counts, last matched timestamps) for supervised learning.
 *
 * Responsibilities:
 * - Retrieve all historical transactions from PartnerRepository
 * - For each transaction, search for matching partner candidates
 * - Update partner learning data (occurrence count, last matched timestamp)
 * - Support dry-run mode for validation without database changes
 * - Track processing statistics (processed, learned, skipped)
 *
 * This service supports the training subsystem that improves partner matching
 * accuracy over time by learning from successful matches.
 */
final class TrainingService implements TrainingServiceInterface
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly PartnerDataServiceInterface $dataService
    ) {}

    /**
     * Build training data from historical transactions
     *
     * Processes all partners by searching for matching historical transactions.
     * Updates occurrence counts and last matched timestamps for learning.
     *
     * Process:
     * 1. Retrieve all partners from repository (across all types)
     * 2. For each partner, search for matching transactions
     * 3. If matches found, update learning data (unless dry run)
     * 4. Track statistics: processed, learned, skipped
     *
     * @param bool $dryRun If true, processes but doesn't modify database
     * @return array{processed: int, learned: int, skipped: int} Training statistics
     */
    public function buildTrainingData(bool $dryRun = false): array
    {
        $stats = [
            'processed' => 0,
            'learned' => 0,
            'skipped' => 0,
        ];

        // Get all partners by iterating through each type
        $allPartners = [];
        foreach (PartnerType::cases() as $type) {
            $allPartners = array_merge($allPartners, $this->partnerRepository->getByType($type));
        }

        foreach ($allPartners as $partner) {
            $stats['processed']++;

            // Search for matching transactions by partner name pattern
            $matches = $this->partnerRepository->searchByPattern(
                $partner->name()
            );

            if (empty($matches)) {
                $stats['skipped']++;
                continue;
            }

            // Partner had matches - mark as learned
            $stats['learned']++;

            // Update occurrence count and timestamp (unless dry run)
            if (!$dryRun) {
                $this->dataService->updateOccurrenceCount(
                    $partner->id(),
                    $partner->type()
                );

                $this->dataService->updateLastMatchedTimestamp(
                    $partner->id(),
                    $partner->type()
                );
            }
        }

        return $stats;
    }
}
