<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BankImportController [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BankImportController.
 */
namespace Ksfraser\FaBankImport\Controller;

use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Services\TransactionViewService;

class BankImportController
{
    private $container;
    private $transactions = [];

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    public function index(): void
    {
        /* Original code replaced by new MVC structure
        $transactions = $this->getAllTransactions();
        foreach ($transactions as $transaction) {
            $this->displayTransaction($transaction);
        }
        */
        
        // Load transactions using service
        $this->transactions = $this->loadTransactions();
        
        // Display transactions using view service
        foreach ($this->transactions as $transaction) {
            $viewService = $this->container->getTransactionViewService($transaction);
            echo $viewService->display();
        }
    }

    private function loadTransactions(): array
    {
        // Get transactions from database
        $result = db_query("SELECT * FROM bi_transactions WHERE status = ?", ['pending']);
        return $result ? $result : [];
    }

    public function processTransaction(int $transactionId, string $type): void
    {
        $transaction = $this->findTransaction($transactionId);
        
        if (!$transaction) {
            throw new \InvalidArgumentException("Transaction not found: $transactionId");
        }

        $transactionObj = $this->container
            ->getTransactionTypeFactory()
            ->createTransactionType($type, $transaction);

        $transactionObj->processTransaction();
    }

    private function findTransaction(int $id): ?array
    {
        $result = db_query("SELECT * FROM bi_transactions WHERE id = ?", [$id]);
        return $result ? $result[0] : null;
    }
}