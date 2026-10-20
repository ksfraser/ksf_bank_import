<?php

namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\Application\Container;
use Ksfraser\Application\Commands\ProcessTransactionCommand;
use Ksfraser\Application\Middleware\AuthMiddleware;

/**
 * @BABOK CLASS ROLLUP: BankImportController — 4 methods; BR-002 (4); FR-008 (4); FR-018 (4)
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: BankImportController.php; coverage: docs/TestCases/ (file rollup)
 */
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
