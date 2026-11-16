<?php

/** @BABOK RTM: ARCH-009 / BR-009 / FR-009 / UAT-001*/
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

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
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

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    private function loadTransactions(): array
    {
        // Get transactions from database
        $result = db_query("SELECT * FROM bi_transactions WHERE status = ?", ['pending']);
        return $this->fetchRows($result);
    }

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
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

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    private function findTransaction(int $id): ?array
    {
        $result = db_query("SELECT * FROM bi_transactions WHERE id = ?", [$id]);
        return $this->fetchRows($result)[0] ?? null;
    }

    /**
     * db_query() answers with a FA result resource at runtime (truthy,
     * rows fetched through db_fetch_assoc()); test doubles may answer
     * with a plain array of rows. Normalise both to an array of rows.
     *
     * @param mixed $result db_query() result.
     * @return array Rows of the result set.
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function fetchRows($result): array
    {
        if (is_array($result)) {
            return $result;
        }
        if ($result === false || $result === null) {
            return [];
        }
        $rows = [];
        while (($row = db_fetch_assoc($result)) !== false) {
            $rows[] = $row;
        }
        return $rows;
    }
}

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class BankImportController extends AbstractController
{
    private $container;

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function __construct($container = null)
    {
        parent::__construct();
        $this->container = $container !== null
            ? $container
            : Container::getInstance();
    }

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    protected function initializeMiddleware(): void
    {
        $this->pipeline->pipe(new AuthMiddleware());
    }

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
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

    /** * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
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
        } catch (\Throwable $e) {
            $this->render('transactions/list', [
                'transactions' => $this->container->getTransactionService()->getPendingTransactions(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
