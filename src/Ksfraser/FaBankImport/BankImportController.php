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
namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\Application\Container;
use Ksfraser\Application\Commands\ProcessTransactionCommand;
use Ksfraser\Application\Middleware\AuthMiddleware;

class BankImportController extends AbstractController
{
    private $container;

    public function __construct()
    {
        parent::__construct();
        $this->container = Container::getInstance();
    }

    protected function initializeMiddleware(): void
    {
        $this->pipeline->pipe(new AuthMiddleware());
    }

    public function index(): void
    {
        $transactionService = $this->container->getTransactionService();
        $transactions = $transactionService->getPendingTransactions();
        
        $this->render('transactions/list', [
            'transactions' => $transactions
        ]);
    }

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
