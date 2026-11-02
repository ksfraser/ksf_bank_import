<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * @BABOK CLASS ROLLUP: PartnerDataServiceInterface — 6 methods; BR-011 (6)
 * @BABOK RTM: BR-011 / ARCH-011; file: Application/Partner/PartnerDataServiceInterface.php; coverage: docs/TestCases/ (file rollup)
 * PartnerDataServiceInterface
 *
 * Interface for partner CRUD operations and data management.
 */
interface PartnerDataServiceInterface
{
    /**
 * @BABOK BR-011 / ARCH-011 — method: getPartnerData
     * Get existing partner or return null
     *
     * @param int $partnerId Partner ID to retrieve
     * @param PartnerType $type Partner type
     * @return PartnerEntity|null
     */
    public function getPartnerData(int $partnerId, PartnerType $type): ?PartnerEntity;

    /**
 * @BABOK BR-011 / ARCH-011 — method: setPartnerData
     * Set (create or update) partner data
     *
     * @param int $partnerId Partner ID (0 for new)
     * @param PartnerType $type Partner type
     * @param string $partnerName Partner name/data to store
     */
    public function setPartnerData(int $partnerId, PartnerType $type, string $partnerName): void;

    /**
 * @BABOK BR-011 / ARCH-011 — method: appendPartnerData
     * Append data to existing partner (accumulation)
     *
     * @param int $partnerId Existing partner ID
     * @param PartnerType $type Partner type
     * @param string $newData Additional data to append
     */
    public function appendPartnerData(int $partnerId, PartnerType $type, string $newData): void;

    /**
 * @BABOK BR-011 / ARCH-011 — method: deletePartnerData
     * Delete partner by ID
     *
     * @param int $partnerId Partner to delete
     * @return bool True if deleted, false if not found
     */
    public function deletePartnerData(int $partnerId): bool;

    /**
 * @BABOK BR-011 / ARCH-011 — method: updateOccurrenceCount
     * Increment occurrence count for a partner
     *
     * @param int $partnerId Partner to update
     * @param PartnerType $type Partner type
     */
    public function updateOccurrenceCount(int $partnerId, PartnerType $type): void;

    /**
 * @BABOK BR-011 / ARCH-011 — method: updateLastMatchedTimestamp
     * Update last matched timestamp to current time
     *
     * @param int $partnerId Partner to update
     * @param PartnerType $type Partner type
     */
    public function updateLastMatchedTimestamp(int $partnerId, PartnerType $type): void;
}
