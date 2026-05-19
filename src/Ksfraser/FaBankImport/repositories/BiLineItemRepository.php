<?php

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Contracts\BiLineItemRepositoryInterface;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\DTOs\BiLineItemDTO;
use Ksfraser\FaBankImport\DTOs\BiLineItemCollectionDTO;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;

/**
 * Mock implementation of BiLineItemRepository
 *
 * Stores line items in memory using mock data.
 * Used for testing and as placeholder until real database implementation.
 *
 * @since 2025-01-15
 */
class BiLineItemRepository implements BiLineItemRepositoryInterface
{
    /** @var BiLineItem[] */
    private array $items = [];

    public function __construct()
    {
        $this->initializeMockData();
    }

    /**
     * Initialize repository with mock data
     */
    private function initializeMockData(): void
    {
        // Create 15 sample line items
        $mockData = [
            ['id' => 1, 'amount' => 150.00, 'matched' => true, 'partnerType' => 'Supplier', 'code' => 'DEP'],
            ['id' => 2, 'amount' => 250.00, 'matched' => false, 'partnerType' => 'Customer', 'code' => 'DEP'],
            ['id' => 3, 'amount' => 350.00, 'matched' => true, 'partnerType' => 'Supplier', 'code' => 'CHK'],
            ['id' => 4, 'amount' => 450.00, 'matched' => false, 'partnerType' => null, 'code' => 'ACH'],
            ['id' => 5, 'amount' => 550.00, 'matched' => true, 'partnerType' => 'Customer', 'code' => 'DEP'],
            ['id' => 6, 'amount' => 100.00, 'matched' => false, 'partnerType' => 'Supplier', 'code' => 'CHK'],
            ['id' => 7, 'amount' => 200.00, 'matched' => true, 'partnerType' => null, 'code' => 'ACH'],
            ['id' => 8, 'amount' => 300.00, 'matched' => false, 'partnerType' => 'Customer', 'code' => 'DEP'],
            ['id' => 9, 'amount' => 400.00, 'matched' => true, 'partnerType' => 'Supplier', 'code' => 'CHK'],
            ['id' => 10, 'amount' => 500.00, 'matched' => false, 'partnerType' => 'Customer', 'code' => 'ACH'],
            ['id' => 11, 'amount' => 600.00, 'matched' => true, 'partnerType' => 'Supplier', 'code' => 'DEP'],
            ['id' => 12, 'amount' => 75.00, 'matched' => false, 'partnerType' => null, 'code' => 'CHK'],
            ['id' => 13, 'amount' => 275.00, 'matched' => true, 'partnerType' => 'Customer', 'code' => 'ACH'],
            ['id' => 14, 'amount' => 425.00, 'matched' => false, 'partnerType' => 'Supplier', 'code' => 'DEP'],
            ['id' => 15, 'amount' => 675.00, 'matched' => true, 'partnerType' => 'Customer', 'code' => 'CHK'],
        ];

        foreach ($mockData as $data) {
            $lineItem = BiLineItem::create([
                'id' => $data['id'],
                'transactionDc' => 'D',
                'our_account' => '1000',
                'valueTimestamp' => date('Y-m-d', strtotime("-{$data['id']} days")),
                'entryTimestamp' => date('Y-m-d', strtotime("-{$data['id']} days")),
                'otherBankaccount' => '4000-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT),
                'otherBankaccountName' => 'Partner ' . $data['id'],
                'transactionTitle' => 'Transaction ' . $data['id'],
                'status' => 0,
                'currency' => 'USD',
                'fa_trans_type' => 0,
                'fa_trans_no' => 0,
                'has_trans' => 1,
                'amount' => $data['amount'],
                'charge' => 0.00,
                'transactionTypeLabel' => 'Debit',
                'vendor_list' => [],
                'partnerType' => $data['partnerType'],
                'partnerId' => null,
                'partnerDetailId' => null,
                'oplabel' => null,
                'matching_trans' => [],
                'days_spread' => 2,
                'transactionCode' => $data['code'],
                'transactionCodeDesc' => 'Code ' . $data['code'],
                'optypes' => [],
                'memo' => 'Memo ' . $data['id'],
                'ourBankDetails' => [],
                'ourBankAccount' => '1000',
                'ourBankAccountName' => 'Our Bank',
                'ourBankAccountCode' => '100',
                'fa_bank_accounts' => null,
                'matched' => $data['matched'],
                'created' => false,
                'formData' => null,
            ]);

            if ($data['matched']) {
                $lineItem = $lineItem->withMatchedStatus(true);
            }

            $this->items[$data['id']] = $lineItem;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): BiLineItem
    {
        if (!isset($this->items[$id])) {
            throw new RepositoryException("BiLineItem with ID {$id} not found");
        }
        return $this->items[$id];
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();

        foreach ($this->items as $item) {
            $matches = true;
            foreach ($criteria as $field => $value) {
                $method = 'get' . ucfirst($field);
                if (method_exists($item, $method)) {
                    if ($item->$method() !== $value) {
                        $matches = false;
                        break;
                    }
                } else {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $collection->add($this->convertToDDTO($item));
            }
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            $collection->add($this->convertToDDTO($item));
        }
        return $collection;
    }

    /**
     * Helper method to convert BiLineItem entity to BiLineItemDTO
     *
     * @param BiLineItem $entity Entity to convert
     * @return BiLineItemDTO Converted DTO
     */
    private function convertToDDTO(BiLineItem $entity): BiLineItemDTO
    {
        return BiLineItemDTO::fromArray($entity->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function save(BiLineItem $lineItem): void
    {
        $this->items[$lineItem->getId()] = $lineItem;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): void
    {
        unset($this->items[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function findMatched(): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if ($item->isMatched()) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findUnmatched(): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if (!$item->isMatched()) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findByAmountRange(float $minAmount, float $maxAmount): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if ($item->getAmount() >= $minAmount && $item->getAmount() <= $maxAmount) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findByTransactionCode(string $code): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if ($item->getTransactionCode() === $code) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findByPartnerType(string $partnerType): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if ($item->getPartnerType() === $partnerType) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findByPartnerId(int $partnerId): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if ($item->getPartnerId() === $partnerId) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function findUnassignedPartners(): BiLineItemCollectionDTO
    {
        $collection = new BiLineItemCollectionDTO();
        foreach ($this->items as $item) {
            if ($item->getPartnerType() === null && $item->getPartnerId() === null) {
                $collection->add($this->convertToDDTO($item));
            }
        }
        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function getSummaryStats(): array
    {
        $all = $this->findAll();
        $matched = $this->findMatched();
        $unmatched = $this->findUnmatched();

        return [
            'total_count' => count($all),
            'matched_count' => count($matched),
            'unmatched_count' => count($unmatched),
            'total_amount' => $all->sumAmounts(),
            'matched_amount' => $matched->sumAmounts(),
            'unmatched_amount' => $unmatched->sumAmounts(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getStatsByPartnerType(): array
    {
        $stats = [];
        $allDTOs = $this->findAll();
        $grouped = $allDTOs->groupBy(fn(BiLineItemDTO $dto) => $dto->getPartnerType() ?? 'Unassigned');

        foreach ($grouped as $type => $collection) {
            $stats[$type] = [
                'count' => count($collection),
                'total_amount' => $collection->sumAmounts(),
                'matched' => count($collection->getMatched()),
            ];
        }

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatsByTransactionCode(): array
    {
        $stats = [];
        $allDTOs = $this->findAll();
        $grouped = $allDTOs->groupBy(fn(BiLineItemDTO $dto) => $dto->getTransactionCode());

        foreach ($grouped as $code => $collection) {
            $stats[$code] = [
                'count' => count($collection),
                'total_amount' => $collection->sumAmounts(),
                'matched' => count($collection->getMatched()),
            ];
        }

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function getMatchStats(): array
    {
        $all = $this->findAll();
        $matched = count($this->findMatched());
        $total = count($all);

        return [
            'total' => $total,
            'matched' => $matched,
            'unmatched' => $total - $matched,
            'match_percentage' => $total > 0 ? round(($matched / $total) * 100, 2) : 0,
        ];
    }
}
