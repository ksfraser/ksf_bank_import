<?php

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Contracts\BiTransactionRepositoryInterface;
use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;
use Ksfraser\FaBankImport\DTOs\BiTransactionCollectionDTO;

/**
 * BiTransactionRepository
 * 
 * Repository implementation for BiTransaction data access.
 * Abstracts database operations from domain logic.
 * Currently uses mock data - ready to connect to actual database layer.
 * 
 * @package Ksfraser\FaBankImport\Repositories
 */
final class BiTransactionRepository implements BiTransactionRepositoryInterface
{
    // Mock database - to be replaced with actual PDO connection
    private array $mockData = [];

    public function __construct()
    {
        // Initialize with mock data for testing
        $this->initializeMockData();
    }

    /**
     * Initialize mock data for testing
     */
    private function initializeMockData(): void
    {
        $baseData = [
            'smtId' => 10,
            'valueTimestamp' => '2026-05-18',
            'entryTimestamp' => '2026-05-18 10:30:00',
            'account' => '1000',
            'accountName' => 'Checking',
            'transactionType' => 'DEBIT',
            'transactionCodeDesc' => 'Check',
            'transactionDC' => 'D',
            'transactionTitle' => 'Payment',
            'status' => 'PENDING',
            'matchinfo' => null,
            'faTransType' => null,
            'faTransNo' => null,
            'fitid' => 'FIT001',
            'acctid' => 'ACC001',
            'merchant' => 'Vendor A',
            'category' => 'OFFICE',
            'sic' => '5411',
            'memo' => 'Test',
            'checknumber' => '001',
            'matched' => false,
            'created' => false,
            'gPartner' => null,
            'gOption' => null,
        ];

        // Create mock transactions
        for ($i = 1; $i <= 15; $i++) {
            $this->mockData[$i] = array_merge($baseData, [
                'id' => $i,
                'transactionCode' => 'CHK' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'transactionAmount' => (100 * $i),
                'matched' => ($i % 3 === 0), // Every 3rd transaction is matched
                'created' => ($i % 2 === 0), // Every 2nd transaction is created
            ]);
        }
    }

    /**
     * Find transaction by ID
     */
    public function findById(int $id): BiTransaction
    {
        $data = $this->mockData[$id] ?? null;
        
        if (!$data) {
            throw new \Exception("BiTransaction with ID {$id} not found");
        }

        return BiTransaction::fromDatabase($data);
    }

    /**
     * Find transaction or return null
     */
    public function findByIdOrNull(int $id): ?BiTransaction
    {
        try {
            return $this->findById($id);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Find all transactions (with pagination)
     */
    public function findAll(int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        $items = array_slice($this->mockData, $offset, $limit);
        $entities = array_map(fn($data) => BiTransaction::fromDatabase($data), $items);
        
        return BiTransactionCollectionDTO::fromArray($entities);
    }

    /**
     * Find by criteria
     */
    public function findBy(array $criteria = [], int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        $filtered = $this->mockData;

        foreach ($criteria as $key => $value) {
            $filtered = array_filter($filtered, function ($item) use ($key, $value) {
                return ($item[$key] ?? null) === $value;
            });
        }

        $items = array_slice($filtered, $offset, $limit);
        $entities = array_map(fn($data) => BiTransaction::fromDatabase($data), $items);

        return BiTransactionCollectionDTO::fromArray($entities);
    }

    /**
     * Find by multiple IDs
     */
    public function findByIds(array $ids): BiTransactionCollectionDTO
    {
        $items = array_intersect_key($this->mockData, array_flip($ids));
        $entities = array_map(fn($data) => BiTransaction::fromDatabase($data), $items);

        return BiTransactionCollectionDTO::fromArray($entities);
    }

    /**
     * Count transactions
     */
    public function count(array $criteria = []): int
    {
        return count($this->findBy($criteria)->all());
    }

    /**
     * Save transaction (insert or update)
     */
    public function save(BiTransaction $transaction): int
    {
        $data = $transaction->toArray();
        $id = $data['id'] ?? 0;

        if ($id === 0) {
            // Insert - generate new ID
            $id = max(array_keys($this->mockData)) + 1;
            $data['id'] = $id;
        }

        $this->mockData[$id] = $data;

        return $id;
    }

    /**
     * Delete transaction
     */
    public function delete(int $id): bool
    {
        if (!isset($this->mockData[$id])) {
            return false;
        }

        unset($this->mockData[$id]);

        return true;
    }

    /**
     * Delete multiple transactions
     */
    public function deleteMultiple(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->delete($id)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if transaction exists
     */
    public function exists(int $id): bool
    {
        return isset($this->mockData[$id]);
    }

    /**
     * Find by statement ID
     */
    public function findByStatementId(int $smtId, int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        return $this->findBy(['smtId' => $smtId], $limit, $offset);
    }

    /**
     * Find matched transactions
     */
    public function findMatched(int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        return $this->findBy(['matched' => true], $limit, $offset);
    }

    /**
     * Find unmatched transactions
     */
    public function findUnmatched(int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        return $this->findBy(['matched' => false], $limit, $offset);
    }

    /**
     * Find by transaction code
     */
    public function findByTransactionCode(string $code, int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        return $this->findBy(['transactionCode' => $code], $limit, $offset);
    }

    /**
     * Find by amount range
     */
    public function findByAmountRange(float $min, float $max, int $limit = 100, int $offset = 0): BiTransactionCollectionDTO
    {
        $filtered = array_filter($this->mockData, function ($item) use ($min, $max) {
            $amount = $item['transactionAmount'] ?? 0;
            return $amount >= $min && $amount <= $max;
        });

        $items = array_slice($filtered, $offset, $limit);
        $entities = array_map(fn($data) => BiTransaction::fromDatabase($data), $items);

        return BiTransactionCollectionDTO::fromArray($entities);
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats(array $criteria = []): array
    {
        $collection = $this->findBy($criteria, limit: 10000); // Get all matching
        $entities = $collection->all();

        if (empty($entities)) {
            return [
                'count' => 0,
                'sum' => 0.00,
                'avg' => 0.00,
                'min' => 0.00,
                'max' => 0.00,
            ];
        }

        $amounts = array_map(fn(BiTransaction $e) => $e->getTransactionAmount(), $entities);

        return [
            'count' => count($amounts),
            'sum' => (float)array_sum($amounts),
            'avg' => (float)array_sum($amounts) / count($amounts),
            'min' => (float)min($amounts),
            'max' => (float)max($amounts),
        ];
    }

    /**
     * Internal method: Get a copy of mock data (for testing/debugging)
     */
    public function getMockData(): array
    {
        return $this->mockData;
    }
}
