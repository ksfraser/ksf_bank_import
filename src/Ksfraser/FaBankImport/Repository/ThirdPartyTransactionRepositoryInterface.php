<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ThirdPartyTransactionRepositoryInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ThirdPartyTransactionRepositoryInterface.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\DTO\BiTransactionDto;

interface ThirdPartyTransactionRepositoryInterface
{
    /**
    * @return array<int, BiTransactionDto>
     */
    public function getAllTransactions(): array;

    public function unsetTransaction($transactionId): bool;

    /**
        * @return BiTransactionDto|null
     */
    public function findById($transactionId): ?BiTransactionDto;

    public function toggleDebitCredit($transactionId): bool;
}
