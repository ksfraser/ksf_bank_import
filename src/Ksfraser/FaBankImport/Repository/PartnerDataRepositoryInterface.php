<?php

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use Ksfraser\FaBankImport\Domain\Exceptions\PartnerDataNotFoundException;

/**
 * Repository interface for partner data operations
 *
 * Defines contract for accessing and persisting partner data entities.
 * Implementations must use prepared statements for security.
 *
 * @package Ksfraser\FaBankImport\Repository
 * @author  Kevin Fraser
 * @version 1.0.0
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
interface PartnerDataRepositoryInterface
{
    /**
     * Find partner data by unique identifiers
     *
     * @param int $partnerId       Partner ID
     * @param int $partnerType     Partner type constant
     * @param int $partnerDetailId Detail ID
     * @param string $data         The keyword/pattern data
     *
     * @return PartnerData|null Partner data or null if not found
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function find(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data
    ): ?PartnerData;

    /**
     * Find all partner data entries for a specific partner
     *
     * @param int $partnerId       Partner ID
     * @param int|null $partnerType Optional partner type filter
     *
     * @return array<PartnerData> Array of PartnerData objects
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function findByPartner(int $partnerId, ?int $partnerType = null): array;

    /**
     * Find partner data entries by keyword using LIKE search
     *
     * Returns all entries where the data field contains the given keyword.
     *
     * @param string   $keyword     Keyword to search for
     * @param int|null $partnerType Optional partner type filter
     *
     * @return array<PartnerData> Array of PartnerData objects
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function findByKeyword(string $keyword, ?int $partnerType = null): array;

    /**
     * Search for partners by multiple keywords with scoring
     *
     * Returns partners that match any of the keywords, with occurrence counts
     * for scoring purposes. Results are grouped by partner.
     *
     * @param array<string> $keywords    Keywords to search for
     * @param int|null      $partnerType Optional partner type filter
     * @param int           $limit       Maximum number of partners to return
     *
     * @return array<array> Array of partner match data with:
     *  - partner_id: int
     *  - partner_type: int
     *  - partner_detail_id: int
     *  - partner_name: string
     *  - matched_keywords: array<string>
     *  - total_score: int (sum of occurrence counts)
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function searchByKeywords(
        array $keywords,
        ?int $partnerType = null,
        int $limit = 10
    ): array;

    /**
     * Save partner data (insert or update)
     *
     * If a record with the same unique key exists, updates the occurrence count.
     * Otherwise, inserts a new record.
     *
     * @param PartnerData $partnerData The partner data to save
     *
     * @return bool True on success
     * @throws \RuntimeException If save operation fails
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function save(PartnerData $partnerData): bool;

    /**
     * Delete partner data
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type
     * @param int    $partnerDetailId Detail ID
     * @param string $data            The keyword/pattern data
     *
     * @return bool True on success
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function delete(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data
    ): bool;

    /**
     * Delete all partner data for a specific partner
     *
     * @param int      $partnerId   Partner ID
     * @param int|null $partnerType Optional partner type filter
     *
     * @return int Number of records deleted
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function deleteByPartner(int $partnerId, ?int $partnerType = null): int;

    /**
     * Increment occurrence count for existing partner data
     *
     * If the record doesn't exist, this method should create it with count = 1.
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type
     * @param int    $partnerDetailId Detail ID
     * @param string $keyword         The keyword to increment
     * @param int    $increment       Amount to increment by (default 1)
     *
     * @return bool True on success
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function incrementOccurrence(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword,
        int $increment = 1
    ): bool;

    /**
     * Get total count of partner data entries
     *
     * @param int|null $partnerType Optional partner type filter
     *
     * @return int Total number of entries
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function count(?int $partnerType = null): int;

    /**
     * Check if partner data exists
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type
     * @param int    $partnerDetailId Detail ID
     * @param string $data            The keyword/pattern data
     *
     * @return bool True if exists
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function exists(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data
    ): bool;

    /**
     * Get top keywords by occurrence count
     *
     * Returns the most frequently occurring keywords across all partners.
     *
     * @param int $limit Number of top keywords to return
     * @param int|null $partnerType Optional partner type filter
     *
     * @return array<array> Array of keyword data with:
     *  - data: string (the keyword)
     *  - total_occurrences: int (sum across all partners)
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTopKeywords(int $limit = 20, ?int $partnerType = null): array;
}
