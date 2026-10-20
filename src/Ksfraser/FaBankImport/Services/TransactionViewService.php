<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;

/**
 * @BABOK CLASS ROLLUP: TransactionViewService — 4 methods; BR-001 (4); BR-002 (4); FR-008 (4)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/TransactionViewService.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionViewService
{
    private $transaction;
    private $view;

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct */
    public function __construct(BankTransactionInterface $transaction, TransactionViewInterface $view)
    {
        $this->transaction = $transaction;
        $this->view = $view;
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: display */
    public function display(): string
    {
        $this->setupViewData();
        return $this->view->render();
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: setupViewData */
    private function setupViewData(): void
    {
        $accountDetails = $this->transaction->getAccountDetails();
        $otherPartyDetails = $this->transaction->getOtherPartyDetails();
        
        // Add action buttons based on transaction type
        $this->setupActionButtons();
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: setupActionButtons */
    private function setupActionButtons(): void
    {
        //TODO
        //I was expecting these to be in the factory created classes.
        switch ($this->transaction->getTransactionType()) {
            case 'C':
                $this->view->addButton('AddCustomer', ['id' => $this->transaction->getId()]);
                break;
            case 'D':
                $this->view->addButton('AddVendor', ['id' => $this->transaction->getId()]);
                break;
        }
        
        $this->view->addButton('ProcessTransaction', ['id' => $this->transaction->getId()]);
    }
}
