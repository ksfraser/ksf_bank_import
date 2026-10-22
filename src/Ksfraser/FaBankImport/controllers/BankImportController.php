<?php

namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\Application\Container;
use Ksfraser\FaBankImport\Services\TransactionViewService;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;


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
class BankImportController extends AbstractController
{
    private $container;

    /**
     * Constructor.
     *
     * @param \Ksfraser\FaBankImport\Container|null $container Optional container
     *        injection for tests; defaults to the shared instance.
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct($container = null)
    {
        parent::__construct();
        $this->container = $container ?? Container::getInstance();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    protected function initializeMiddleware(): void
    {
        $this->pipeline->pipe(new AuthMiddleware());
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function index(): void
    {
        $transactionService = $this->container->getTransactionService();
        $transactions = $transactionService->getPendingTransactions();
        
        $this->render('transactions/list', [
            'transactions' => $transactions
        ]);
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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
