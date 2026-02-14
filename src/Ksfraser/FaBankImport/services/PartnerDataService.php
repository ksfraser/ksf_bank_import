<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :PartnerDataService [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for PartnerDataService.
 */
namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use Ksfraser\FaBankImport\Domain\Exceptions\PartnerDataNotFoundException;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;
use InvalidArgumentException;

/**
 * Service for partner data CRUD operations
 *
 * Provides business logic layer for managing partner keyword data,
 * including validation, deduplication, and batch operations.
 *
 * @package Ksfraser\FaBankImport\Services
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class PartnerDataService
{
    /**
     * @var PartnerDataRepositoryInterface Repository for partner data
     */
    private PartnerDataRepositoryInterface $repository;

    /**
     * @var KeywordExtractorService Keyword extraction service
     */
    private KeywordExtractorService $extractor;

    /**
     * Constructor
     *
     * @param PartnerDataRepositoryInterface $repository Partner data repository
     * @param KeywordExtractorService        $extractor  Keyword extraction service
     */
    public function __construct(
        PartnerDataRepositoryInterface $repository,
        KeywordExtractorService $extractor
    ) {
        $this->repository = $repository;
        $this->extractor = $extractor;
    }

    /**
     * Save partner data entry
     *
     * Creates or updates a partner data entry. If the entry already exists,
     * updates the occurrence count.
     *
     * @param PartnerData $partnerData Partner data to save
     *
     * @return bool True on success
     * @throws \RuntimeException If save fails
     */
    public function save(PartnerData $partnerData): bool
    {
        return $this->repository->save($partnerData);
    }

    /**
     * Save a keyword for a partner
     *
     * Convenience method that creates a PartnerData value object and saves it.
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID
     * @param string $keyword         Keyword to save
     * @param int    $occurrenceCount Initial occurrence count (default 1)
     *
     * @return bool True on success
     * @throws InvalidArgumentException If keyword is invalid
     */
    public function saveKeyword(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword,
        int $occurrenceCount = 1
    ): bool {
        // Validate keyword
        if (!$this->extractor->isValid($keyword)) {
            throw new InvalidArgumentException("Invalid keyword: {$keyword}");
        }

        $partnerData = new PartnerData(
            $partnerId,
            $partnerType,
            $partnerDetailId,
            $keyword,
            $occurrenceCount
        );

        return $this->repository->save($partnerData);
    }

    /**
     * Extract and save keywords from text for a partner
     *
     * Extracts all valid keywords from text and saves them for the partner.
     * Updates occurrence count if keyword already exists.
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID
     * @param string $text            Text to extract keywords from
     *
     * @return int Number of keywords saved
     */
    public function saveKeywordsFromText(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $text
    ): int {
        $keywords = $this->extractor->extractAsStrings($text);
        
        $savedCount = 0;
        foreach ($keywords as $keyword) {
            try {
                if ($this->saveKeyword($partnerId, $partnerType, $partnerDetailId, $keyword)) {
                    $savedCount++;
                }
            } catch (InvalidArgumentException $e) {
                // Skip invalid keywords
                continue;
            }
        }

        return $savedCount;
    }

    /**
     * Increment occurrence count for a keyword
     *
     * Atomically increments the occurrence count. If the keyword doesn't exist,
     * creates it with the increment value as initial count.
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID
     * @param string $keyword         Keyword to increment
     * @param int    $increment       Amount to increment by (default 1)
     *
     * @return bool True on success
     */
    public function incrementKeywordOccurrence(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword,
        int $increment = 1
    ): bool {
        return $this->repository->incrementOccurrence(
            $partnerId,
            $partnerType,
            $partnerDetailId,
            $keyword,
            $increment
        );
    }

    /**
     * Find partner data by unique key
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID
     * @param string $keyword         Keyword data
     *
     * @return PartnerData|null Partner data or null if not found
     */
    public function find(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword
    ): ?PartnerData {
        return $this->repository->find($partnerId, $partnerType, $partnerDetailId, $keyword);
    }

    /**
     * Get all keywords for a partner
     *
     * @param int      $partnerId   Partner ID
     * @param int|null $partnerType Optional partner type filter
     *
     * @return array<PartnerData> Array of PartnerData objects
     */
    public function getPartnerKeywords(int $partnerId, ?int $partnerType = null): array
    {
        return $this->repository->findByPartner($partnerId, $partnerType);
    }

    /**
     * Delete partner data entry
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID
     * @param string $keyword         Keyword to delete
     *
     * @return bool True on success
     */
    public function delete(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword
    ): bool {
        return $this->repository->delete($partnerId, $partnerType, $partnerDetailId, $keyword);
    }

    /**
     * Delete all keywords for a partner
     *
     * @param int      $partnerId   Partner ID
     * @param int|null $partnerType Optional partner type filter
     *
     * @return int Number of entries deleted
     */
    public function deletePartnerKeywords(int $partnerId, ?int $partnerType = null): int
    {
        return $this->repository->deleteByPartner($partnerId, $partnerType);
    }

    /**
     * Check if a keyword exists for a partner
     *
     * @param int    $partnerId       Partner ID
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID
     * @param string $keyword         Keyword to check
     *
     * @return bool True if exists
     */
    public function exists(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword
    ): bool {
        return $this->repository->exists($partnerId, $partnerType, $partnerDetailId, $keyword);
    }

    /**
     * Get total count of partner data entries
     *
     * @param int|null $partnerType Optional partner type filter
     *
     * @return int Total number of entries
     */
    public function count(?int $partnerType = null): int
    {
        return $this->repository->count($partnerType);
    }

    /**
     * Get top keywords across all partners
     *
     * Returns the most frequently occurring keywords.
     *
     * @param int      $limit       Number of top keywords to return
     * @param int|null $partnerType Optional partner type filter
     *
     * @return array<array> Array of keyword data with 'data' and 'total_occurrences'
     */
    public function getTopKeywords(int $limit = 20, ?int $partnerType = null): array
    {
        return $this->repository->getTopKeywords($limit, $partnerType);
    }

    /**
     * Rebuild keywords for a partner from current transaction data
     *
     * This method would be used to reprocess existing transactions and rebuild
     * the keyword index. Implementation requires access to transaction data.
     *
     * @param int      $partnerId   Partner ID
     * @param int|null $partnerType Optional partner type filter
     *
     * @return int Number of keywords rebuilt
     * @todo Implement when transaction access is available
     */
    public function rebuildPartnerKeywords(int $partnerId, ?int $partnerType = null): int
    {
        // TODO: This would:
        // 1. Delete existing keywords for partner
        // 2. Find all transactions for partner
        // 3. Extract keywords from transaction memos/descriptions
        // 4. Save new keywords with occurrence counts
        
        throw new \BadMethodCallException('rebuildPartnerKeywords() not yet implemented');
    }
}
