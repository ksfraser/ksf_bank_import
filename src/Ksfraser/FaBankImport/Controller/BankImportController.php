<?php

namespace Ksfraser\FaBankImport\Controller;

use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Services\TransactionViewService;

/**
 * @BABOK CLASS ROLLUP: BankImportController — 5 methods; BR-002 (5); FR-008 (5); FR-018 (5)
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Controller/BankImportController.php; coverage: docs/TestCases/ (file rollup)
 */
class BankImportController
{
    private $container;
    private $transactions = [];

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: index */
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

    /** @BABOK BR-002 / FR-008 / FR-018 — method: loadTransactions */
    private function loadTransactions(): array
    {
        // Get transactions from database
        $result = db_query("SELECT * FROM bi_transactions WHERE status = ?", ['pending']);
        return $result ? $result : [];
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: processTransaction */
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

    /** @BABOK BR-002 / FR-008 / FR-018 — method: findTransaction */
    private function findTransaction(int $id): ?array
    {
        $result = db_query("SELECT * FROM bi_transactions WHERE id = ?", [$id]);
        return $result ? $result[0] : null;
    }
}