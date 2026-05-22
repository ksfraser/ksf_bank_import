<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Services\BiLineItemService;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;
use DateTime;
use DateTimeImmutable;

/**
 * BiLineItemCommandHandler
 *
 * Command/request handling layer for bank import line items.
 *
 * Coordinates with services and provides structured response formatting
 * for API requests and CLI commands. Handles data transformation,
 * error handling, and response metadata.
 *
 * @package Ksfraser\FaBankImport\Commands
 * @since 2025-01-15
 */
class BiLineItemCommandHandler
{
    public function __construct(
        private BiLineItemService $service
    ) {
    }

    /**
     * List all line items
     *
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleListAll(int $limit = 0, int $offset = 0): array
    {
        try {
            $items = $this->service->getAllLineItems();
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * List matched line items
     *
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleListMatched(int $limit = 0, int $offset = 0): array
    {
        try {
            $items = $this->service->getMatchedLineItems();
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * List unmatched line items
     *
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleListUnmatched(int $limit = 0, int $offset = 0): array
    {
        try {
            $items = $this->service->getUnmatchedLineItems();
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get a single line item by ID
     *
     * @param int $id Line item ID
     * @return array<string, mixed> Formatted response
     */
    public function handleGetById(int $id): array
    {
        try {
            $item = $this->service->getLineItemById($id);
            $data = $item->toArray();

            return $this->successResponse($data);
        } catch (RepositoryException $e) {
            return $this->errorResponse("Line item with ID {$id} not found");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Count line items
     *
     * @return array<string, mixed> Formatted response with counts
     */
    public function handleCount(): array
    {
        try {
            $total = $this->service->countAllLineItems();
            $matched = $this->service->countMatchedLineItems();
            $unmatched = $this->service->countUnmatchedLineItems();

            $data = [
                'count' => $total,
                'matched_count' => $matched,
                'unmatched_count' => $unmatched,
            ];

            $response = $this->successResponse([], $total);
            $response['matched_count'] = $matched;
            $response['unmatched_count'] = $unmatched;

            return $response;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get summary statistics
     *
     * @return array<string, mixed> Formatted response with stats
     */
    public function handleGetStats(): array
    {
        try {
            $stats = $this->service->getSummaryStats();

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get statistics by partner type
     *
     * @return array<string, mixed> Formatted response with stats
     */
    public function handleGetStatsByPartnerType(): array
    {
        try {
            $stats = $this->service->getStatsByPartnerType();

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get statistics by transaction code
     *
     * @return array<string, mixed> Formatted response with stats
     */
    public function handleGetStatsByTransactionCode(): array
    {
        try {
            $stats = $this->service->getStatsByTransactionCode();

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get match statistics
     *
     * @return array<string, mixed> Formatted response with match stats
     */
    public function handleGetMatchStats(): array
    {
        try {
            $stats = $this->service->getMatchStats();

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Filter by amount range
     *
     * @param float $minAmount Minimum amount (inclusive)
     * @param float $maxAmount Maximum amount (inclusive)
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleFilterByAmountRange(
        float $minAmount,
        float $maxAmount,
        int $limit = 0,
        int $offset = 0
    ): array {
        try {
            $items = $this->service->filterByAmountRange($minAmount, $maxAmount);
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Filter by partner type
     *
     * @param string $partnerType Partner type to filter by
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleFilterByPartnerType(
        string $partnerType,
        int $limit = 0,
        int $offset = 0
    ): array {
        try {
            $items = $this->service->filterByPartnerType($partnerType);
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Filter by transaction code
     *
     * @param string $code Transaction code to filter by
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleFilterByTransactionCode(
        string $code,
        int $limit = 0,
        int $offset = 0
    ): array {
        try {
            $items = $this->service->filterByTransactionCode($code);
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get unassigned partners
     *
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleGetUnassignedPartners(int $limit = 0, int $offset = 0): array
    {
        try {
            $items = $this->service->getUnassignedPartners();
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Save a line item
     *
     * @param array<string, mixed> $data Line item data
     * @return array<string, mixed> Formatted response
     */
    public function handleSave(array $data): array
    {
        try {
            $lineItem = BiLineItem::create($data);
            $this->service->saveLineItem($lineItem);

            return $this->successResponse(['id' => $lineItem->getId()], 1);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Delete a line item
     *
     * @param int $id Line item ID to delete
     * @return array<string, mixed> Formatted response
     */
    public function handleDelete(int $id): array
    {
        try {
            $this->service->deleteLineItem($id);

            return $this->successResponse(['id' => $id]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Find by complex criteria
     *
     * @param array<string, mixed> $criteria Criteria to match
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<string, mixed> Formatted response
     */
    public function handleFindByCriteria(
        array $criteria,
        int $limit = 0,
        int $offset = 0
    ): array {
        try {
            $items = $this->service->findByCriteria($criteria);
            $data = $this->convertCollectionToArray($items, $limit, $offset);

            return $this->successResponse($data, count($data));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Convert DTO collection to array with optional pagination
     *
     * @param \Ksfraser\FaBankImport\DTOs\BiLineItemCollectionDTO $collection DTOs to convert
     * @param int $limit Maximum items to return (0 = unlimited)
     * @param int $offset Starting position
     * @return array<int, array<string, mixed>> Array of DTOs
     */
    private function convertCollectionToArray($collection, int $limit = 0, int $offset = 0): array
    {
        $result = [];
        $count = 0;
        $skipped = 0;

        foreach ($collection as $dto) {
            // Skip items before offset
            if ($skipped < $offset) {
                $skipped++;
                continue;
            }

            // Stop if limit reached
            if ($limit > 0 && $count >= $limit) {
                break;
            }

            $result[] = $dto->toArray();
            $count++;
        }

        return $result;
    }

    /**
     * Build success response
     *
     * @param mixed $data Response data
     * @param int $count Item count (optional)
     * @return array<string, mixed> Formatted response
     */
    private function successResponse($data, int $count = 1): array
    {
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => $this->getCurrentTimestamp(),
        ];

        if ($count > 0) {
            $response['count'] = $count;
        }

        return $response;
    }

    /**
     * Build error response
     *
     * @param string $errorMessage Error message
     * @return array<string, mixed> Formatted error response
     */
    private function errorResponse(string $errorMessage): array
    {
        return [
            'success' => false,
            'error' => $errorMessage,
            'timestamp' => $this->getCurrentTimestamp(),
        ];
    }

    /**
     * Get current timestamp in ISO 8601 format
     *
     * @return string Timestamp
     */
    private function getCurrentTimestamp(): string
    {
        return (new DateTimeImmutable())->format('c');
    }
}
