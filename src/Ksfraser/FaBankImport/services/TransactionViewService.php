<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionViewService [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionViewService.
 */
namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;

class TransactionViewService
{
    private $transaction;
    private $view;

    public function __construct(BankTransactionInterface $transaction, TransactionViewInterface $view)
    {
        $this->transaction = $transaction;
        $this->view = $view;
    }

    public function display(): string
    {
        $this->setupViewData();
        return $this->view->render();
    }

    private function setupViewData(): void
    {
        $accountDetails = $this->transaction->getAccountDetails();
        $otherPartyDetails = $this->transaction->getOtherPartyDetails();
        
        // Add action buttons based on transaction type
        $this->setupActionButtons();
    }

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
