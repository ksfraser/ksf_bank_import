<?php
/**
 * Command Pattern Bootstrap
 *
 * Sets up the Command Pattern architecture for POST action handling.
 * Include this file in process_statements.php to enable the new architecture.
 *
 * @package Ksfraser\FaBankImport
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */

echo "DEBUG: command_bootstrap.php started\n";

use Ksfraser\FaBankImport\Container\SimpleContainer;
use Ksfraser\FaBankImport\Commands\CommandDispatcher;

echo "DEBUG: use statements declared\n";

// ============================================================================
// FEATURE FLAG - Toggle between old/new implementations
// ============================================================================

echo "DEBUG: About to check USE_COMMAND_PATTERN\n";
if (!defined('USE_COMMAND_PATTERN')) {
    /**
     * Feature flag for Command Pattern
     * Set to true to use new architecture, false for legacy code
     */
    define('USE_COMMAND_PATTERN', true);
}
echo "DEBUG: USE_COMMAND_PATTERN defined\n";

// ============================================================================
// INITIALIZE DI CONTAINER
// ============================================================================

echo "DEBUG: About to initialize DI container\n";
if (!isset($container)) {
    $container = new SimpleContainer();
    echo "DEBUG: SimpleContainer created\n";
    
    // Bind repositories (existing models)
    if (isset($bi_transactions_model)) {
        $container->instance('TransactionRepository', $bi_transactions_model);
        echo "DEBUG: TransactionRepository bound\n";
    }
    
    // Bind legacy controller (for transitional period)
    if (isset($bi_controller)) {
        $container->instance('LegacyController', $bi_controller);
        echo "DEBUG: LegacyController bound\n";
    }
    
    // Bind services (these can be created as they're extracted)
    // Example future bindings:
    // $container->bind('CustomerService', CustomerService::class);
    // $container->bind('VendorService', VendorService::class);
    // $container->bind('TransactionService', TransactionService::class);
}
echo "DEBUG: DI container initialized\n";

// ============================================================================
// INITIALIZE COMMAND DISPATCHER
// ============================================================================

echo "DEBUG: About to initialize CommandDispatcher\n";
if (!isset($commandDispatcher)) {
    $commandDispatcher = new CommandDispatcher($container);
    echo "DEBUG: CommandDispatcher created\n";
}
echo "DEBUG: CommandDispatcher initialized\n";

// ============================================================================
// POST ACTION HANDLER
// ============================================================================

/**
 * Handle POST actions using Command Pattern
 *
 * @param CommandDispatcher $dispatcher
 * @param array $postData
 * @return \Ksfraser\FaBankImport\Results\TransactionResult|null
 */
function handleCommandAction(CommandDispatcher $dispatcher, array $postData): ?\Ksfraser\FaBankImport\Results\TransactionResult
{
    $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
    
    foreach ($actions as $action) {
        if (isset($postData[$action])) {
            return $dispatcher->dispatch($action, $postData);
        }
    }
    
    return null;
}

/**
 * Handle POST actions using legacy code
 *
 * @param object $bi_controller
 * @param object|null $Ajax
 * @return void
 */
function handleLegacyAction($bi_controller, $Ajax = null): void
{
    if (isset($_POST['UnsetTrans'])) {
        $bi_controller->unsetTrans();
        if ($Ajax) {
            $Ajax->activate('doc_tbl');
        }
    }
    
    if (isset($_POST['AddCustomer'])) {
        $bi_controller->addCustomer();
        if ($Ajax) {
            $Ajax->activate('doc_tbl');
        }
    }
    
    if (isset($_POST['AddVendor'])) {
        $bi_controller->addVendor();
        if ($Ajax) {
            $Ajax->activate('doc_tbl');
        }
    }
    
    if (isset($_POST['ToggleTransaction'])) {
        $bi_controller->toggleDebitCredit();
        if ($Ajax) {
            $Ajax->activate('doc_tbl');
        }
    }
}

// ============================================================================
// MAIN POST HANDLER
// ============================================================================

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !defined('COMMAND_HANDLER_PROCESSED')) {
    define('COMMAND_HANDLER_PROCESSED', true);
    
    if (USE_COMMAND_PATTERN) {
        // NEW IMPLEMENTATION: Command Pattern
        $result = handleCommandAction($commandDispatcher, $_POST);
        
        if ($result !== null) {
            $result->display();
            
            if (isset($Ajax)) {
                $Ajax->activate('doc_tbl');
            }
        }
    } else {
        // OLD IMPLEMENTATION: Legacy Code
        if (isset($bi_controller)) {
            handleLegacyAction($bi_controller, $Ajax ?? null);
        }
    }
}

echo "DEBUG: command_bootstrap.php completed successfully\n";
