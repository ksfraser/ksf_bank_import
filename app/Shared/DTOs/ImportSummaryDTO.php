<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

/**
 * Aggregates import operation results for display/reporting
 */
class ImportSummaryDTO
{
    public $results; // array of import operation results

    public function __construct(array $data = [])
    {
        $this->results = $data['results'] ?? $data['importResults'] ?? [];
    }

    /**
     * Get the total number of results
     */
    public function getResultCount(): int
    {
        return count($this->results);
    }

    /**
     * Get only successful results
     */
    public function getSuccessfulResults(): array
    {
        return array_filter($this->results, function ($result) {
            return ($result['success'] ?? false) === true;
        });
    }

    /**
     * Get only failed results
     */
    public function getFailedResults(): array
    {
        return array_filter($this->results, function ($result) {
            return ($result['success'] ?? false) !== true;
        });
    }

    /**
     * Get the success rate as a percentage
     */
    public function getSuccessRate(): float
    {
        $total = $this->getResultCount();
        if ($total === 0) return 0;
        return (count($this->getSuccessfulResults()) / $total) * 100;
    }
}
