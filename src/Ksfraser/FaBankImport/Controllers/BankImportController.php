<?php

/** @BABOK RTM: ARCH-009 / BR-009 / FR-009 / UAT-001*/
namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\Application\Container;
use Ksfraser\FaBankImport\Services\TransactionViewService;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;


/**
 * @BABOK CLASS ROLLUP: BankImportController_MVC — 9 methods; BR-002 (9); FR-008 (9); FR-018 (9)
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Controllers/BankImportController.php; coverage: docs/TestCases/ (file rollup)
 */
class BankImportController_MVC
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

class BankImportController extends AbstractController
{
    private $container;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct()
    {
        parent::__construct();
        $this->container = Container::getInstance();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: initializeMiddleware */
    protected function initializeMiddleware(): void
    {
        $this->pipeline->pipe(new AuthMiddleware());
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: index */
    public function index(): void
    {
        $transactionService = $this->container->getTransactionService();
        $transactions = $transactionService->getPendingTransactions();
        
        $this->render('transactions/list', [
            'transactions' => $transactions
        ]);
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: process */
    public function process(): void
    {
        $command = $this->request->getTransactionCommand();
        
        if (!$command) {
            $this->redirect($_SERVER['PHP_SELF']);
            return;
        }

        try {
            $processCommand = new ProcessTransactionCommand(
                $command['id'],
                $command['type'],
                $_SESSION['user_id'] ?? null
            );

            $this->container->getCommandBus()->dispatch($processCommand);
            $this->redirect($_SERVER['PHP_SELF'] . '?success=1');
        } catch (\Exception $e) {
            $this->render('transactions/list', [
                'transactions' => $this->container->getTransactionService()->getPendingTransactions(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
