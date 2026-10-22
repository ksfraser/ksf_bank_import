<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;

/**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */
class TransactionViewService
{
    private $transaction;
    private $view;

    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(BankTransactionInterface $transaction, TransactionViewInterface $view)
    {
        $this->transaction = $transaction;
        $this->view = $view;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function display(): string
    {
        $this->setupViewData();
        return $this->view->render();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    private function setupViewData(): void
    {
        $accountDetails = $this->transaction->getAccountDetails();
        $otherPartyDetails = $this->transaction->getOtherPartyDetails();
        
        // Add action buttons based on transaction type
        $this->setupActionButtons();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
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
