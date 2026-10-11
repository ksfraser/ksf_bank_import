<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;
use InvalidArgumentException;
use DomainException;

/**
 * PartnerDataService
 * 
 * Wrapper service for partner CRUD operations.
 * Replaces direct calls to pdata.inc functions:
 * - get_partner_data()
 * - set_partner_data()
 * - update_partner_data()
 * 
 * This service:
 * - Validates input before persistence
 * - Prevents duplicate/unchanged writes
 * - Handles occurrence count tracking
 * - Manages last_matched_ts updates
 */
final class PartnerDataService implements PartnerDataServiceInterface
{
    private const MAX_PARTNER_DATA_LENGTH = 2000;

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
    ) {}

    /**
     * Get existing partner or return null
     * 
     * @param int $partnerId Partner ID to retrieve
     * @param PartnerType $type Partner type (unused here but kept for compatibility)
     * @return PartnerEntity|null
     */
    public function getPartnerData(int $partnerId, PartnerType $type): ?PartnerEntity
    {
        return $this->partnerRepository->getById($partnerId);
    }

    /**
     * Set (create or update) partner data
     * 
     * - If partner doesn't exist, creates new
     * - If exists with same name, returns early (no-op)
     * - If exists with different name, updates
     * 
     * @param int $partnerId Partner ID (0 for new)
     * @param PartnerType $type Partner type
     * @param string $partnerName Partner name/data to store
     * @throws InvalidArgumentException If name is empty
     */
    public function setPartnerData(int $partnerId, PartnerType $type, string $partnerName): void
    {
        $partnerName = trim($partnerName);
        
        if (empty($partnerName)) {
            throw new InvalidArgumentException('Partner name cannot be empty');
        }

        // Try to fetch existing
        $existing = $partnerId > 0 ? $this->partnerRepository->getById($partnerId) : null;

        // Short-circuit if unchanged
        if ($existing && $existing->name() === $partnerName) {
            return; // No-op: already has same name
        }

        // Create or update
        if ($existing) {
            // Update existing with new name, preserving other fields
            $updated = new PartnerEntity(
                id: $existing->id(),
                name: $partnerName,
                type: $existing->type(),
                occurrenceCount: $existing->occurrenceCount(),
                lastMatchedTs: $existing->lastMatchedTs(),
            );
            $this->partnerRepository->update($updated);
        } else {
            // Create new
            $partner = new PartnerEntity(
                id: 0,
                name: $partnerName,
                type: $type,
                occurrenceCount: 1,
                lastMatchedTs: null,
            );
            $this->partnerRepository->create($partner);
        }
    }

    /**
     * Append data to existing partner (accumulation)
     * 
     * Concatenates new data with newline separator.
     * Throws exception if combined length exceeds limit.
     * 
     * @param int $partnerId Existing partner ID
     * @param PartnerType $type Partner type
     * @param string $newData Additional data to append
     * @throws DomainException If append would exceed max length
     */
    public function appendPartnerData(int $partnerId, PartnerType $type, string $newData): void
    {
        $existing = $this->partnerRepository->getById($partnerId);
        
        if (!$existing) {
            // Create new partner instead
            $this->setPartnerData($partnerId, $type, $newData);
            return;
        }

        $combined = $existing->name() . "\n" . $newData;

        if (strlen($combined) > self::MAX_PARTNER_DATA_LENGTH) {
            throw new DomainException(
                sprintf(
                    'Partner data exceeds maximum length of %d characters',
                    self::MAX_PARTNER_DATA_LENGTH
                )
            );
        }

        $updated = new PartnerEntity(
            id: $existing->id(),
            name: $combined,
            type: $existing->type(),
            occurrenceCount: $existing->occurrenceCount(),
            lastMatchedTs: $existing->lastMatchedTs(),
        );

        $this->partnerRepository->update($updated);
    }

    /**
     * Delete partner by ID
     * 
     * @param int $partnerId Partner to delete
     * @return bool True if deleted, false if not found
     */
    public function deletePartnerData(int $partnerId): bool
    {
        return $this->partnerRepository->delete($partnerId);
    }

    /**
     * Increment occurrence count for a partner
     * 
     * Used when a partner is matched successfully.
     * Preserves other fields (name, type, timestamp).
     * 
     * @param int $partnerId Partner to update
     * @param PartnerType $type Partner type
     */
    public function updateOccurrenceCount(int $partnerId, PartnerType $type): void
    {
        $existing = $this->partnerRepository->getById($partnerId);
        
        if (!$existing) {
            return; // Cannot increment non-existent partner
        }

        $updated = new PartnerEntity(
            id: $existing->id(),
            name: $existing->name(),
            type: $existing->type(),
            occurrenceCount: $existing->occurrenceCount() + 1,
            lastMatchedTs: $existing->lastMatchedTs(),
        );

        $this->partnerRepository->update($updated);
    }

    /**
     * Update last matched timestamp to current time
     * 
     * Used when a partner is successfully matched to a transaction.
     * Preserves other fields (name, occurrence count).
     * 
     * @param int $partnerId Partner to update
     * @param PartnerType $type Partner type
     */
    public function updateLastMatchedTimestamp(int $partnerId, PartnerType $type): void
    {
        $existing = $this->partnerRepository->getById($partnerId);
        
        if (!$existing) {
            return; // Cannot update non-existent partner
        }

        $updated = new PartnerEntity(
            id: $existing->id(),
            name: $existing->name(),
            type: $existing->type(),
            occurrenceCount: $existing->occurrenceCount(),
            lastMatchedTs: new \DateTime(),
        );

        $this->partnerRepository->update($updated);
    }
}
