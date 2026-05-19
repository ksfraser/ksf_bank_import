<?php

namespace Ksfraser\FaBankImport\Contracts;

use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\DTOs\BiLineItemCollectionDTO;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;

/**
 * Repository interface for BiLineItem data access
 *
 * Defines the contract for accessing and persisting BiLineItem entities.
 * Implementations should handle both entity and DTO conversions.
 *
 * @since 2025-01-15
 */
interface BiLineItemRepositoryInterface
{
    /**
     * Find a line item by ID
     *
     * @param int $id Line item ID
     * @return BiLineItem The line item
     * @throws RepositoryException If not found or database error
     */
    public function findById(int $id): BiLineItem;

    /**
     * Find multiple line items by criteria
     *
     * @param array $criteria Search criteria (field => value pairs)
     * @return BiLineItemCollectionDTO Matching line items
     * @throws RepositoryException On database error
     */
    public function findBy(array $criteria): BiLineItemCollectionDTO;

    /**
     * Get all line items
     *
     * @return BiLineItemCollectionDTO All line items
     * @throws RepositoryException On database error
     */
    public function findAll(): BiLineItemCollectionDTO;

    /**
     * Count total line items
     *
     * @return int Total count
     * @throws RepositoryException On database error
     */
    public function count(): int;

    /**
     * Save a line item (insert or update)
     *
     * @param BiLineItem $lineItem Line item to save
     * @return void
     * @throws RepositoryException On database error
     */
    public function save(BiLineItem $lineItem): void;

    /**
     * Delete a line item by ID
     *
     * @param int $id Line item ID
     * @return void
     * @throws RepositoryException On database error
     */
    public function delete(int $id): void;

    /**
     * Find only matched line items
     *
     * @return BiLineItemCollectionDTO Matched line items
     * @throws RepositoryException On database error
     */
    public function findMatched(): BiLineItemCollectionDTO;

    /**
     * Find only unmatched line items
     *
     * @return BiLineItemCollectionDTO Unmatched line items
     * @throws RepositoryException On database error
     */
    public function findUnmatched(): BiLineItemCollectionDTO;

    /**
     * Find line items by amount range
     *
     * @param float $minAmount Minimum amount
     * @param float $maxAmount Maximum amount
     * @return BiLineItemCollectionDTO Line items within range
     * @throws RepositoryException On database error
     */
    public function findByAmountRange(float $minAmount, float $maxAmount): BiLineItemCollectionDTO;

    /**
     * Find line items by transaction code
     *
     * @param string $code Transaction code
     * @return BiLineItemCollectionDTO Matching line items
     * @throws RepositoryException On database error
     */
    public function findByTransactionCode(string $code): BiLineItemCollectionDTO;

    /**
     * Find line items by partner type
     *
     * @param string $partnerType Partner type (Supplier, Customer, etc.)
     * @return BiLineItemCollectionDTO Matching line items
     * @throws RepositoryException On database error
     */
    public function findByPartnerType(string $partnerType): BiLineItemCollectionDTO;

    /**
     * Find line items by partner ID
     *
     * @param int $partnerId Partner ID
     * @return BiLineItemCollectionDTO Matching line items
     * @throws RepositoryException On database error
     */
    public function findByPartnerId(int $partnerId): BiLineItemCollectionDTO;

    /**
     * Find line items with no partner assigned
     *
     * @return BiLineItemCollectionDTO Unassigned line items
     * @throws RepositoryException On database error
     */
    public function findUnassignedPartners(): BiLineItemCollectionDTO;

    /**
     * Get summary statistics
     *
     * @return array Statistics including total_amount, matched_count, unmatched_count, etc.
     * @throws RepositoryException On database error
     */
    public function getSummaryStats(): array;

    /**
     * Get statistics grouped by partner type
     *
     * @return array Statistics by partner type
     * @throws RepositoryException On database error
     */
    public function getStatsByPartnerType(): array;

    /**
     * Get statistics grouped by transaction code
     *
     * @return array Statistics by transaction code
     * @throws RepositoryException On database error
     */
    public function getStatsByTransactionCode(): array;

    /**
     * Get match statistics (percentage matched, etc.)
     *
     * @return array Match statistics
     * @throws RepositoryException On database error
     */
    public function getMatchStats(): array;
}
