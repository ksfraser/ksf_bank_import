<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Contracts\BiLineItemRepositoryInterface;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\DTOs\BiLineItemDTO;
use Ksfraser\FaBankImport\DTOs\BiLineItemCollectionDTO;

/**
 * BiLineItemService
 *
 * Business logic orchestration layer for bank import line items.
 *
 * Coordinates DTOs, repositories, and complex domain operations.
 * Provides high-level API for working with line items while maintaining
 * separation of concerns between data access and business logic.
 *
 * @package Ksfraser\FaBankImport\Services
 * @since 2025-01-15
 */
class BiLineItemService
{
    public function __construct(
        private BiLineItemRepositoryInterface $repository
    ) {
    }

    /**
     * Get all line items as DTOs
     *
     * @return BiLineItemCollectionDTO Collection of all line items
     */
    public function getAllLineItems(): BiLineItemCollectionDTO
    {
        return $this->repository->findAll();
    }

    /**
     * Get all matched line items
     *
     * @return BiLineItemCollectionDTO Collection of matched items
     */
    public function getMatchedLineItems(): BiLineItemCollectionDTO
    {
        return $this->repository->findMatched();
    }

    /**
     * Get all unmatched line items
     *
     * @return BiLineItemCollectionDTO Collection of unmatched items
     */
    public function getUnmatchedLineItems(): BiLineItemCollectionDTO
    {
        return $this->repository->findUnmatched();
    }

    /**
     * Count all line items
     *
     * @return int Total number of line items
     */
    public function countAllLineItems(): int
    {
        return $this->repository->count();
    }

    /**
     * Count matched line items
     *
     * @return int Number of matched items
     */
    public function countMatchedLineItems(): int
    {
        return count($this->repository->findMatched());
    }

    /**
     * Count unmatched line items
     *
     * @return int Number of unmatched items
     */
    public function countUnmatchedLineItems(): int
    {
        return count($this->repository->findUnmatched());
    }

    /**
     * Get a single line item by ID
     *
     * @param int $id Line item ID
     * @return BiLineItemDTO The requested line item
     * @throws \Ksfraser\FaBankImport\Exceptions\RepositoryException When not found
     */
    public function getLineItemById(int $id): BiLineItemDTO
    {
        $entity = $this->repository->findById($id);
        return BiLineItemDTO::fromArray($entity->toArray());
    }

    /**
     * Filter line items by amount range
     *
     * @param float $minAmount Minimum amount (inclusive)
     * @param float $maxAmount Maximum amount (inclusive)
     * @return BiLineItemCollectionDTO Filtered collection
     */
    public function filterByAmountRange(float $minAmount, float $maxAmount): BiLineItemCollectionDTO
    {
        return $this->repository->findByAmountRange($minAmount, $maxAmount);
    }

    /**
     * Filter line items by partner type
     *
     * @param string $partnerType Partner type to filter by
     * @return BiLineItemCollectionDTO Filtered collection
     */
    public function filterByPartnerType(string $partnerType): BiLineItemCollectionDTO
    {
        return $this->repository->findByPartnerType($partnerType);
    }

    /**
     * Filter line items by transaction code
     *
     * @param string $code Transaction code to filter by
     * @return BiLineItemCollectionDTO Filtered collection
     */
    public function filterByTransactionCode(string $code): BiLineItemCollectionDTO
    {
        return $this->repository->findByTransactionCode($code);
    }

    /**
     * Get line items without assigned partners
     *
     * @return BiLineItemCollectionDTO Collection of unassigned items
     */
    public function getUnassignedPartners(): BiLineItemCollectionDTO
    {
        return $this->repository->findUnassignedPartners();
    }

    /**
     * Get summary statistics for all line items
     *
     * Returns totals for matched/unmatched items and their amounts.
     *
     * @return array<string, int|float> Statistics array with keys:
     *   - total_count: int
     *   - matched_count: int
     *   - unmatched_count: int
     *   - total_amount: float
     *   - matched_amount: float
     *   - unmatched_amount: float
     */
    public function getSummaryStats(): array
    {
        return $this->repository->getSummaryStats();
    }

    /**
     * Get statistics grouped by partner type
     *
     * @return array<string, array<string, int|float>> Statistics by partner type
     */
    public function getStatsByPartnerType(): array
    {
        return $this->repository->getStatsByPartnerType();
    }

    /**
     * Get statistics grouped by transaction code
     *
     * @return array<string, array<string, int|float>> Statistics by transaction code
     */
    public function getStatsByTransactionCode(): array
    {
        return $this->repository->getStatsByTransactionCode();
    }

    /**
     * Get match statistics (totals and percentage)
     *
     * @return array<string, int|float> Match statistics with keys:
     *   - total_items: int
     *   - matched_items: int
     *   - unmatched_items: int
     *   - match_percentage: float (0-100)
     */
    public function getMatchStats(): array
    {
        $stats = $this->repository->getSummaryStats();

        $matchPercentage = 0.0;
        if ($stats['total_count'] > 0) {
            $matchPercentage = ($stats['matched_count'] / $stats['total_count']) * 100;
        }

        return [
            'total_items' => $stats['total_count'],
            'matched_items' => $stats['matched_count'],
            'unmatched_items' => $stats['unmatched_count'],
            'match_percentage' => $matchPercentage,
        ];
    }

    /**
     * Get total amount across all line items
     *
     * @return float Sum of all amounts
     */
    public function getTotalAmount(): float
    {
        return $this->getAllLineItems()->sumAmounts();
    }

    /**
     * Get total amount for matched items
     *
     * @return float Sum of matched item amounts
     */
    public function getMatchedAmount(): float
    {
        return $this->getMatchedLineItems()->sumAmounts();
    }

    /**
     * Get total amount for unmatched items
     *
     * @return float Sum of unmatched item amounts
     */
    public function getUnmatchedAmount(): float
    {
        return $this->getUnmatchedLineItems()->sumAmounts();
    }

    /**
     * Save a line item to the repository
     *
     * @param BiLineItem $lineItem Entity to save
     * @return void
     */
    public function saveLineItem(BiLineItem $lineItem): void
    {
        $this->repository->save($lineItem);
    }

    /**
     * Delete a line item from the repository
     *
     * @param int $id ID of item to delete
     * @return void
     */
    public function deleteLineItem(int $id): void
    {
        $this->repository->delete($id);
    }

    /**
     * Find line items by complex criteria
     *
     * @param array<string, mixed> $criteria Criteria to match
     * @return BiLineItemCollectionDTO Matching items
     */
    public function findByCriteria(array $criteria): BiLineItemCollectionDTO
    {
        return $this->repository->findBy($criteria);
    }

    /**
     * Transform a collection of DTOs using a callback function
     *
     * Applies a transformation function to each DTO and returns array of results.
     * Useful for converting DTOs to presentation formats or extracting specific fields.
     *
     * @template T
     * @param BiLineItemCollectionDTO $items Collection to transform
     * @param callable(BiLineItemDTO): T $transformer Transformation function
     * @return array<T> Array of transformed items
     */
    public function transformLineItems(
        BiLineItemCollectionDTO $items,
        callable $transformer
    ): array {
        $results = [];
        foreach ($items as $item) {
            $results[] = $transformer($item);
        }
        return $results;
    }

    /**
     * Filter a collection of DTOs using a predicate function
     *
     * Applies a filter predicate to each DTO and returns new collection
     * containing only items where predicate returns true.
     *
     * @param BiLineItemCollectionDTO $items Collection to filter
     * @param callable(BiLineItemDTO): bool $predicate Filter predicate
     * @return BiLineItemCollectionDTO Filtered collection
     */
    public function filterLineItems(
        BiLineItemCollectionDTO $items,
        callable $predicate
    ): BiLineItemCollectionDTO {
        return $items->filter($predicate);
    }
}
