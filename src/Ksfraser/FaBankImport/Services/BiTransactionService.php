<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Contracts\BiTransactionRepositoryInterface;
use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;
use Ksfraser\FaBankImport\DTOs\BiTransactionCollectionDTO;

/**
 * BiTransactionService
 * 
 * Service layer for BiTransaction business logic.
 * Orchestrates repository operations and enforces business rules.
 * Uses dependency injection for repository - testable and flexible.
 * 
 * @package Ksfraser\FaBankImport\Services
 */
final class BiTransactionService
{
    public function __construct(
        private BiTransactionRepositoryInterface $repository
    ) {
    }

    /**
     * Get single transaction by ID
     */
    public function getTransaction(int $id): BiTransaction
    {
        return $this->repository->findById($id);
    }

    /**
     * List all transactions with pagination
     */
    public function listAllTransactions(int $page = 1, int $pageSize = 50): array
    {
        $total = $this->repository->count();
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findAll(limit: $pageSize, offset: $offset);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * List matched transactions
     */
    public function listMatchedTransactions(int $page = 1, int $pageSize = 50): array
    {
        $total = $this->repository->count(['matched' => true]);
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findMatched(limit: $pageSize, offset: $offset);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * List unmatched transactions
     */
    public function listUnmatchedTransactions(int $page = 1, int $pageSize = 50): array
    {
        $total = $this->repository->count(['matched' => false]);
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findUnmatched(limit: $pageSize, offset: $offset);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * Toggle debit/credit and persist
     */
    public function toggleDebitCredit(int $id): BiTransaction
    {
        $transaction = $this->repository->findById($id);
        $toggled = $transaction->toggleDebitCredit();
        $this->repository->save($toggled);

        return $toggled;
    }

    /**
     * Mark transaction as matched and persist
     */
    public function markAsMatched(int $id, ?string $matchinfo = null): BiTransaction
    {
        $transaction = $this->repository->findById($id);
        $marked = $transaction->withMatchedStatus(true);

        if ($matchinfo !== null) {
            $marked = $marked->withMatchinfo($matchinfo);
        }

        $this->repository->save($marked);

        return $marked;
    }

    /**
     * Mark transaction as created and persist
     */
    public function markAsCreated(int $id): BiTransaction
    {
        $transaction = $this->repository->findById($id);
        $marked = $transaction->withCreatedStatus(true);
        $this->repository->save($marked);

        return $marked;
    }

    /**
     * Link transaction to FA transaction and persist
     */
    public function linkToFATransaction(int $id, int $faTransNo, int $faTransType): BiTransaction
    {
        $transaction = $this->repository->findById($id);
        $linked = $transaction->withFaTransactionReference($faTransNo, $faTransType);
        $this->repository->save($linked);

        return $linked;
    }

    /**
     * Set partner info and persist
     */
    public function setPartnerInfo(int $id, string $partnerId, string $partnerOption): BiTransaction
    {
        $transaction = $this->repository->findById($id);
        $updated = $transaction->withPartner($partnerId, $partnerOption);
        $this->repository->save($updated);

        return $updated;
    }

    /**
     * Get debit transactions
     */
    public function getDebitTransactions(int $page = 1, int $pageSize = 50): array
    {
        $total = $this->repository->count(['transactionDC' => 'D']);
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findBy(['transactionDC' => 'D'], limit: $pageSize, offset: $offset);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * Get credit transactions
     */
    public function getCreditTransactions(int $page = 1, int $pageSize = 50): array
    {
        $total = $this->repository->count(['transactionDC' => 'C']);
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findBy(['transactionDC' => 'C'], limit: $pageSize, offset: $offset);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * Search transactions by code
     */
    public function searchByCode(string $code, int $page = 1, int $pageSize = 50): array
    {
        $total = $this->repository->count(['transactionCode' => $code]);
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findByTransactionCode($code, limit: $pageSize, offset: $offset);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * Find transactions by amount range
     */
    public function findByAmountRange(float $min, float $max, int $page = 1, int $pageSize = 50): array
    {
        $offset = ($page - 1) * $pageSize;
        $items = $this->repository->findByAmountRange($min, $max, limit: $pageSize, offset: $offset);
        
        // Count all in range (would need repository support for better efficiency)
        $allInRange = $this->repository->findByAmountRange($min, $max, limit: 10000, offset: 0);
        $total = count($allInRange);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStatistics(): array
    {
        $totalStats = $this->repository->getSummaryStats();
        $matchedStats = $this->repository->getSummaryStats(['matched' => true]);
        $unmatchedStats = $this->repository->getSummaryStats(['matched' => false]);

        return [
            'totalCount' => $totalStats['count'],
            'matchedCount' => $matchedStats['count'],
            'unmatchedCount' => $unmatchedStats['count'],
            'totalAmount' => $totalStats['sum'],
            'averageAmount' => $totalStats['avg'],
            'minAmount' => $totalStats['min'],
            'maxAmount' => $totalStats['max'],
        ];
    }

    /**
     * Save transaction to repository
     */
    public function saveTransaction(BiTransaction $transaction): int
    {
        return $this->repository->save($transaction);
    }

    /**
     * Delete transaction
     */
    public function deleteTransaction(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get percentage of matched transactions
     */
    public function getMatchedPercentage(): float
    {
        $stats = $this->getTransactionStatistics();
        
        if ($stats['totalCount'] === 0) {
            return 0.0;
        }

        return round(($stats['matchedCount'] / $stats['totalCount']) * 100, 2);
    }

    /**
     * Get summary for specific statement
     */
    public function getSummaryByStatement(int $smtId): array
    {
        $stats = $this->repository->getSummaryStats(['smtId' => $smtId]);
        
        return [
            'count' => $stats['count'],
            'sum' => $stats['sum'],
            'avg' => $stats['avg'],
            'min' => $stats['min'],
            'max' => $stats['max'],
        ];
    }

    /**
     * Bulk mark transactions as matched
     */
    public function bulkMarkAsMatched(array $ids): array
    {
        $results = [];
        
        foreach ($ids as $id) {
            try {
                $this->markAsMatched($id);
                $results[] = true;
            } catch (\Exception) {
                $results[] = false;
            }
        }

        return $results;
    }

    /**
     * Bulk delete transactions
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->deleteMultiple($ids);
    }

    /**
     * Convert entity to DTO
     */
    public function convertToDTO(BiTransaction $transaction): BiTransactionDTO
    {
        return BiTransactionDTO::fromArray($transaction->toArray());
    }

    /**
     * Convert collection of entities to array of DTOs
     */
    public function convertCollectionToDTOs(BiTransactionCollectionDTO $collection): array
    {
        $dtos = [];
        
        foreach ($collection as $entity) {
            $dtos[] = $this->convertToDTO($entity);
        }

        return $dtos;
    }
}
