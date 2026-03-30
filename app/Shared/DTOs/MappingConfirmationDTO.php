<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

/**
 * Wrapper for account or partner mapping candidates awaiting user confirmation
 */
class MappingConfirmationDTO
{
    public $pendingMappings; // array of pending account/partner mappings

    public function __construct(array $data = [])
    {
        $this->pendingMappings = $data['pendingMappings'] ?? [];
    }

    /**
     * Get the number of pending mappings
     */
    public function getPendingCount(): int
    {
        return count($this->pendingMappings);
    }

    /**
     * Check if there are any pending mappings
     */
    public function hasPendingMappings(): bool
    {
        return $this->getPendingCount() > 0;
    }

    /**
     * Add a pending mapping
     */
    public function addPendingMapping(array $mapping): void
    {
        $this->pendingMappings[] = $mapping;
    }
}
