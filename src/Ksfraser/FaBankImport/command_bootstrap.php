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

bank_import_debug("command_bootstrap.php starting");

use Ksfraser\FaBankImport\Container\SimpleContainer;
use Ksfraser\FaBankImport\Commands\CommandDispatcher;

// ============================================================================
// FEATURE FLAG - Toggle between old/new implementations
// ============================================================================

bank_import_debug("Checking USE_COMMAND_PATTERN flag");
if (!defined('USE_COMMAND_PATTERN')) {
    /**
     * Feature flag for Command Pattern
     * Set to true to use new architecture, false for legacy code
     */
    define('USE_COMMAND_PATTERN', true);
}
bank_import_debug("USE_COMMAND_PATTERN defined", USE_COMMAND_PATTERN);

// ============================================================================
// INITIALIZE DI CONTAINER
// ============================================================================

bank_import_debug("Initializing DI container");
if (!isset($container)) {
    $container = new SimpleContainer();
    bank_import_debug("SimpleContainer created");

    // Bind repositories (existing models)
    if (isset($bit)) {
        $container->instance('TransactionRepository', $bit);
        bank_import_debug("TransactionRepository bound to \$bit");
    } elseif (isset($bi_transactions_model)) {
        $container->instance('TransactionRepository', $bi_transactions_model);
        bank_import_debug("TransactionRepository bound to \$bi_transactions_model");
    } else {
        bank_import_debug("No transaction repository found to bind");
    }

    // Bind legacy controller (for transitional period)
    if (isset($bi_controller)) {
        $container->instance('LegacyController', $bi_controller);
        bank_import_debug("LegacyController bound");
    }

    // Bind services (these can be created as they're extracted)
    // Example future bindings:
    // $container->bind('CustomerService', CustomerService::class);
    // $container->bind('VendorService', VendorService::class);
    // $container->bind('TransactionService', TransactionService::class);
}
bank_import_debug("DI container initialized");// ============================================================================
// INITIALIZE COMMAND DISPATCHER
// ============================================================================

bank_import_debug("Initializing CommandDispatcher");
bank_import_debug("Container check", ['type' => gettype($container), 'class' => is_object($container) ? get_class($container) : 'not object']);
if (!isset($commandDispatcher)) {
    bank_import_debug("CommandDispatcher not set, about to instantiate");
    try {
        $commandDispatcher = new CommandDispatcher($container);
        bank_import_debug("CommandDispatcher instantiated successfully");
    } catch (Throwable $e) {
        bank_import_debug("CommandDispatcher instantiation failed", $e->getMessage());
        bank_import_debug("Stack trace", $e->getTraceAsString());
        die();
    }
} else {
    bank_import_debug("CommandDispatcher already exists", gettype($commandDispatcher));
}
bank_import_debug("CommandDispatcher initialization completed");

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

bank_import_debug("Checking for POST request", ['method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown', 'processed' => defined('COMMAND_HANDLER_PROCESSED')]);
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !defined('COMMAND_HANDLER_PROCESSED')) {
    define('COMMAND_HANDLER_PROCESSED', true);
    bank_import_debug("Processing POST request", $_POST);
    
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

bank_import_debug("command_bootstrap.php completed");
