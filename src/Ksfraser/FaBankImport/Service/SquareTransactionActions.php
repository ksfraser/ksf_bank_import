<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\Repository\ThirdPartyTransactionRepositoryInterface;
use Ksfraser\FaBankImport\DTO\BiTransactionDto;

class SquareTransactionActions implements ThirdPartyTransactionActionsInterface
{
    /** @var ThirdPartyTransactionRepositoryInterface */
    private $repository;

    public function __construct(ThirdPartyTransactionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllTransactions(): array
    {
        $dtos = $this->repository->getAllTransactions();
        return array_map(static function (BiTransactionDto $dto): array {
            return $dto->toArray();
        }, $dtos);
    }

    public function unsetTransaction($transactionId): void
    {
        $this->repository->unsetTransaction($transactionId);
    }

    public function addCustomerFromTransaction($transactionId): void
    {
        $dto = $this->repository->findById($transactionId);
        if ($dto) {
            my_add_customer($dto->toArray());
        }
    }

    public function addVendorFromTransaction($transactionId): void
    {
        $dto = $this->repository->findById($transactionId);
        if ($dto) {
            add_vendor($dto->toArray());
        }
    }

    public function processSupplierTransaction($transactionId): void
    {
        // TODO: implement real processing (likely delegates to existing handlers)
        $this->repository->findById($transactionId);
    }

    public function processCustomerTransaction($transactionId): void
    {
        // TODO: implement real processing (likely delegates to existing handlers)
        $this->repository->findById($transactionId);
    }

    public function processBankTransfer($transactionId): void
    {
        // TODO: implement real processing (likely delegates to existing handlers)
        $this->repository->findById($transactionId);
    }

    public function toggleDebitCredit($transactionId): void
    {
        $this->repository->toggleDebitCredit($transactionId);
    }
}
