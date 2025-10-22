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

use Ksfraser\FaBankImport\Container\SimpleContainer;
use Ksfraser\FaBankImport\Commands\CommandDispatcher;

// ============================================================================
// FEATURE FLAG - Toggle between old/new implementations
// ============================================================================

if (!defined('USE_COMMAND_PATTERN')) {
    /**
     * Feature flag for Command Pattern
     * Set to true to use new architecture, false for legacy code
     */
    define('USE_COMMAND_PATTERN', true);
}

// ============================================================================
// INITIALIZE DI CONTAINER
// ============================================================================

if (!isset($container)) {
    $container = new SimpleContainer();
    
    // Bind repositories (existing models)
    if (isset($bi_transactions_model)) {
        $container->instance('TransactionRepository', $bi_transactions_model);
    }
    
    // Bind legacy controller (for transitional period)
    if (isset($bi_controller)) {
        $container->instance('LegacyController', $bi_controller);
    }
    
    // Bind services (these can be created as they're extracted)
    // Example future bindings:
    // $container->bind('CustomerService', CustomerService::class);
    // $container->bind('VendorService', VendorService::class);
    // $container->bind('TransactionService', TransactionService::class);
}

// ============================================================================
// INITIALIZE COMMAND DISPATCHER
// ============================================================================

if (!isset($commandDispatcher)) {
    $commandDispatcher = new CommandDispatcher($container);
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('COMMAND_HANDLER_PROCESSED')) {
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
