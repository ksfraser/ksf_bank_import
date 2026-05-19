<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Integration;

use Ksfraser\FaBankImport\Repositories\BiLineItemRepository;
use Ksfraser\FaBankImport\Services\BiLineItemService;
use Ksfraser\FaBankImport\Commands\BiLineItemCommandHandler;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;

/**
 * BiLineItemIntegration
 *
 * Bridge between legacy codebase and new architecture.
 * Provides backward-compatible API while leveraging modern service layer.
 *
 * Usage in process_statements.php:
 *   $integration = BiLineItemIntegration::getInstance();
 *   $items = $integration->getLineItems($filter, $offset, $limit);
 *   $stats = $integration->getStatistics();
 *
 * @package Ksfraser\FaBankImport\Integration
 */
class BiLineItemIntegration
{
    private static ?self $instance = null;
    private BiLineItemService $service;
    private BiLineItemCommandHandler $handler;

    private function __construct()
    {
        // Initialize with real repository (can be swapped for DB-backed repo)
        $repository = new BiLineItemRepository();
        $this->service = new BiLineItemService($repository);
        $this->handler = new BiLineItemCommandHandler($this->service);
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all line items for display (legacy compatible)
     *
     * Returns array of DTOs converted to arrays for legacy code compatibility.
     *
     * @param array $filter Optional filter criteria
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit
     * @return array Array of line item arrays
     */
    public function getLineItems(array $filter = [], int $offset = 0, int $limit = 0): array
    {
        try {
            if (!empty($filter)) {
                $collection = $this->service->findByCriteria($filter);
            } else {
                $collection = $this->service->getAllLineItems();
            }

            // Convert to array format for legacy code
            $result = [];
            $count = 0;
            $skipped = 0;

            foreach ($collection as $dto) {
                if ($skipped < $offset) {
                    $skipped++;
                    continue;
                }
                if ($limit > 0 && $count >= $limit) {
                    break;
                }
                $result[] = $dto->toArray();
                $count++;
            }

            return $result;
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getLineItems failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get matched line items
     *
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit
     * @return array Array of matched line item arrays
     */
    public function getMatchedLineItems(int $offset = 0, int $limit = 0): array
    {
        try {
            $collection = $this->service->getMatchedLineItems();
            return $this->collectionToArray($collection, $offset, $limit);
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getMatchedLineItems failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unmatched line items
     *
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit
     * @return array Array of unmatched line item arrays
     */
    public function getUnmatchedLineItems(int $offset = 0, int $limit = 0): array
    {
        try {
            $collection = $this->service->getUnmatchedLineItems();
            return $this->collectionToArray($collection, $offset, $limit);
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getUnmatchedLineItems failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single line item by ID
     *
     * @param int $id Line item ID
     * @return array Line item as array, or empty array if not found
     */
    public function getLineItemById(int $id): array
    {
        try {
            $dto = $this->service->getLineItemById($id);
            return $dto->toArray();
        } catch (RepositoryException $e) {
            error_log("BiLineItemIntegration::getLineItemById($id) not found");
            return [];
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getLineItemById failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get statistics
     *
     * Returns summary stats, match percentage, amounts, etc.
     *
     * @return array Associative array with statistics
     */
    public function getStatistics(): array
    {
        try {
            return $this->service->getMatchStats();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getStatistics failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of all line items
     *
     * @return int Total count
     */
    public function getCount(): int
    {
        try {
            return $this->service->countAllLineItems();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of matched items
     *
     * @return int Matched count
     */
    public function getMatchedCount(): int
    {
        try {
            return $this->service->countMatchedLineItems();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getMatchedCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of unmatched items
     *
     * @return int Unmatched count
     */
    public function getUnmatchedCount(): int
    {
        try {
            return $this->service->countUnmatchedLineItems();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getUnmatchedCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Save line item
     *
     * @param array $data Line item data
     * @return array Response with success status and ID
     */
    public function saveLineItem(array $data): array
    {
        try {
            $lineItem = BiLineItem::create($data);
            $this->service->saveLineItem($lineItem);
            return ['success' => true, 'id' => $lineItem->getId()];
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::saveLineItem failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete line item
     *
     * @param int $id Line item ID
     * @return array Response with success status
     */
    public function deleteLineItem(int $id): array
    {
        try {
            $this->service->deleteLineItem($id);
            return ['success' => true, 'id' => $id];
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::deleteLineItem failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get total amount across all items
     *
     * @return float Total amount
     */
    public function getTotalAmount(): float
    {
        try {
            return $this->service->getTotalAmount();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getTotalAmount failed: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get amount for matched items
     *
     * @return float Matched amount
     */
    public function getMatchedAmount(): float
    {
        try {
            return $this->service->getMatchedAmount();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getMatchedAmount failed: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get amount for unmatched items
     *
     * @return float Unmatched amount
     */
    public function getUnmatchedAmount(): float
    {
        try {
            return $this->service->getUnmatchedAmount();
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::getUnmatchedAmount failed: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Filter by amount range
     *
     * @param float $minAmount Minimum amount
     * @param float $maxAmount Maximum amount
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit
     * @return array Filtered line items
     */
    public function filterByAmountRange(float $minAmount, float $maxAmount, int $offset = 0, int $limit = 0): array
    {
        try {
            $collection = $this->service->filterByAmountRange($minAmount, $maxAmount);
            return $this->collectionToArray($collection, $offset, $limit);
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::filterByAmountRange failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Filter by partner type
     *
     * @param string $partnerType Partner type
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit
     * @return array Filtered line items
     */
    public function filterByPartnerType(string $partnerType, int $offset = 0, int $limit = 0): array
    {
        try {
            $collection = $this->service->filterByPartnerType($partnerType);
            return $this->collectionToArray($collection, $offset, $limit);
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::filterByPartnerType failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Filter by transaction code
     *
     * @param string $code Transaction code
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit
     * @return array Filtered line items
     */
    public function filterByTransactionCode(string $code, int $offset = 0, int $limit = 0): array
    {
        try {
            $collection = $this->service->filterByTransactionCode($code);
            return $this->collectionToArray($collection, $offset, $limit);
        } catch (\Exception $e) {
            error_log("BiLineItemIntegration::filterByTransactionCode failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Convert collection to array format with pagination
     *
     * @param mixed $collection DTO collection
     * @param int $offset Pagination offset
     * @param int $limit Pagination limit (0 = unlimited)
     * @return array Array of DTOs as arrays
     */
    private function collectionToArray($collection, int $offset = 0, int $limit = 0): array
    {
        $result = [];
        $count = 0;
        $skipped = 0;

        foreach ($collection as $dto) {
            if ($skipped < $offset) {
                $skipped++;
                continue;
            }
            if ($limit > 0 && $count >= $limit) {
                break;
            }
            $result[] = $dto->toArray();
            $count++;
        }

        return $result;
    }

    /**
     * Get the underlying service (for advanced usage)
     *
     * @return BiLineItemService
     */
    public function getService(): BiLineItemService
    {
        return $this->service;
    }

    /**
     * Get the underlying command handler (for API usage)
     *
     * @return BiLineItemCommandHandler
     */
    public function getHandler(): BiLineItemCommandHandler
    {
        return $this->handler;
    }
}
