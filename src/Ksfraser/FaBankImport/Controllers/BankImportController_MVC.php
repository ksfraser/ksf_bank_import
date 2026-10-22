<?php

namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\Application\Container;
use Ksfraser\FaBankImport\Services\TransactionViewService;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;


/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */



/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */

class BankImportController_MVC
{
    private $container;
    private $transactions = [];

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function loadTransactions(): array
    {
        // Get transactions from database
        $result = db_query("SELECT * FROM bi_transactions WHERE status = ?", ['pending']);
        return $result ? $result : [];
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function findTransaction(int $id): ?array
    {
        $result = db_query("SELECT * FROM bi_transactions WHERE id = ?", [$id]);
        return $result ? $result[0] : null;
    }
}

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
