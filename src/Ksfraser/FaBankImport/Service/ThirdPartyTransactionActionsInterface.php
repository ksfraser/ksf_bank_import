<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ThirdPartyTransactionActionsInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ThirdPartyTransactionActionsInterface.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

interface ThirdPartyTransactionActionsInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllTransactions(): array;

    public function unsetTransaction($transactionId): void;

    public function addCustomerFromTransaction($transactionId): void;

    public function addVendorFromTransaction($transactionId): void;

    public function processSupplierTransaction($transactionId): void;

    public function processCustomerTransaction($transactionId): void;

    public function processBankTransfer($transactionId): void;

    public function toggleDebitCredit($transactionId): void;
}
