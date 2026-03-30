<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

/**
 * Carries duplicate detection results for user resolution
 */
class DuplicateResolutionDTO
{
    public $duplicates; // array of detected duplicate transactions/matches

    public function __construct(array $data = [])
    {
        $this->duplicates = $data['duplicates'] ?? [];
    }

    /**
     * Get the number of duplicate groups
     */
    public function getDuplicateCount(): int
    {
        return count($this->duplicates);
    }

    /**
     * Check if there are any duplicates
     */
    public function hasDuplicates(): bool
    {
        return $this->getDuplicateCount() > 0;
    }

    /**
     * Get the total number of duplicate transactions across all groups
     */
    public function getTotalDuplicateTransactionCount(): int
    {
        $total = 0;
        foreach ($this->duplicates as $group) {
            $total += count($group['transactions'] ?? []);
        }
        return $total;
    }

    /**
     * Add a duplicate group
     */
    public function addDuplicateGroup(array $group): void
    {
        $this->duplicates[] = $group;
    }
}
