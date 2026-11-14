<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001

 * PartnerDataServiceInterface
 *
 * Interface for partner CRUD operations and data management.
 */
interface PartnerDataServiceInterface
{
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get existing partner or return null
     *
     * @param int $partnerId Partner ID to retrieve
     * @param PartnerType $type Partner type
     * @return PartnerEntity|null
     */
    public function getPartnerData(int $partnerId, PartnerType $type): ?PartnerEntity;

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Set (create or update) partner data
     *
     * @param int $partnerId Partner ID (0 for new)
     * @param PartnerType $type Partner type
     * @param string $partnerName Partner name/data to store
     */
    public function setPartnerData(int $partnerId, PartnerType $type, string $partnerName): void;

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Append data to existing partner (accumulation)
     *
     * @param int $partnerId Existing partner ID
     * @param PartnerType $type Partner type
     * @param string $newData Additional data to append
     */
    public function appendPartnerData(int $partnerId, PartnerType $type, string $newData): void;

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Delete partner by ID
     *
     * @param int $partnerId Partner to delete
     * @return bool True if deleted, false if not found
     */
    public function deletePartnerData(int $partnerId): bool;

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Increment occurrence count for a partner
     *
     * @param int $partnerId Partner to update
     * @param PartnerType $type Partner type
     */
    public function updateOccurrenceCount(int $partnerId, PartnerType $type): void;

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Update last matched timestamp to current time
     *
     * @param int $partnerId Partner to update
     * @param PartnerType $type Partner type
     */
    public function updateLastMatchedTimestamp(int $partnerId, PartnerType $type): void;
}
