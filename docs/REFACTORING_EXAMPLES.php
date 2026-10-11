<?php
/**
 * Process Statements - POST Action Handler Refactoring Example
 *
 * This file shows how to refactor the procedural POST handling
 * in process_statements.php to use the Command Pattern.
 *
 * BEFORE: Lines 100-130 had procedural if statements
 * AFTER: Clean delegation to CommandDispatcher
 *
 * @package Ksfraser\FaBankImport
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */

use Ksfraser\FaBankImport\Commands\CommandDispatcher;
use Ksfraser\FaBankImport\Results\TransactionResult;

// ============================================================================
// EXAMPLE 1: Basic Usage (Minimal Refactor)
// ============================================================================

/**
 * Simple refactor - Replace procedural POST handling
 *
 * Add this near the top of process_statements.php, after includes
 */
function example1_basic_usage(): void
{
    // Initialize dispatcher (do this ONCE at top of file)
    global $container; // Assume you have a DI container
    $commandDispatcher = new CommandDispatcher($container);

    // Handle POST actions (REPLACES lines 100-130)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = handlePostAction($commandDispatcher, $_POST);
        
        if ($result !== null) {
            $result->display();
            
            // Refresh Ajax table if needed
            if (class_exists('Ajax') && isset($Ajax)) {
                $Ajax->activate('doc_tbl');
            }
        }
    }
}

/**
 * Determine which POST action was submitted and dispatch it
 *
 * @param CommandDispatcher $dispatcher The command dispatcher
 * @param array $postData POST data
 * @return TransactionResult|null Result or null if no action
 */
function handlePostAction(CommandDispatcher $dispatcher, array $postData): ?TransactionResult
{
    $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
    
    foreach ($actions as $action) {
        if (isset($postData[$action])) {
            return $dispatcher->dispatch($action, $postData);
        }
    }
    
    return null; // No action submitted
}

// ============================================================================
// EXAMPLE 2: With Error Handling
// ============================================================================

/**
 * Enhanced version with better error handling
 */
function example2_with_error_handling(): void
{
    global $container;
    
    try {
        $commandDispatcher = new CommandDispatcher($container);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = handlePostActionSafely($commandDispatcher, $_POST);
            
            if ($result !== null) {
                $result->display();
                
                // Log successful operations
                if ($result->isSuccess()) {
                    error_log(sprintf(
                        'Command executed successfully: %s',
                        $result->getMessage()
                    ));
                }
                
                // Refresh UI
                if (isset($Ajax)) {
                    $Ajax->activate('doc_tbl');
                }
            }
        }
    } catch (\Exception $e) {
        // Fallback error display
        display_error('An error occurred processing your request: ' . $e->getMessage());
    }
}

function handlePostActionSafely(CommandDispatcher $dispatcher, array $postData): ?TransactionResult
{
    $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
    
    foreach ($actions as $action) {
        if (isset($postData[$action])) {
            try {
                return $dispatcher->dispatch($action, $postData);
            } catch (\Exception $e) {
                return TransactionResult::error(
                    sprintf('Failed to execute %s: %s', $action, $e->getMessage())
                );
            }
        }
    }
    
    return null;
}

// ============================================================================
// EXAMPLE 3: Full Production Implementation
// ============================================================================

/**
 * Production-ready implementation with:
 * - CSRF protection check
 * - Permission verification
 * - Detailed logging
 * - Event dispatching
 */
function example3_production_implementation(): void
{
    global $container, $Ajax;
    
    // ========================================================================
    // 1. Initialize dispatcher
    // ========================================================================
    $commandDispatcher = new CommandDispatcher($container);
    
    // ========================================================================
    // 2. Handle POST requests
    // ========================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token (if using)
        if (!verifyCsrfToken($_POST['_token'] ?? '')) {
            display_error('Invalid security token');
            return;
        }
        
        // Check user permissions
        if (!hasPermission('SA_BANKIMPORT')) {
            display_error('Insufficient permissions');
            return;
        }
        
        // Dispatch command
        $result = handlePostActionProduction($commandDispatcher, $_POST);
        
        if ($result !== null) {
            // Display result to user
            $result->display();
            
            // Log operation
            logCommandExecution($result);
            
            // Refresh UI components
            refreshAjaxComponents($Ajax, $result);
            
            // Redirect on success (PRG pattern)
            if ($result->isSuccess() && shouldRedirect()) {
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

function handlePostActionProduction(CommandDispatcher $dispatcher, array $postData): ?TransactionResult
{
    $actions = [
        'UnsetTrans',
        'AddCustomer',
        'AddVendor',
        'ToggleTransaction',
        'ProcessTransaction' // Keep existing processor for this one
    ];
    
    foreach ($actions as $action) {
        if (isset($postData[$action])) {
            // Special handling for ProcessTransaction (uses TransactionProcessor)
            if ($action === 'ProcessTransaction') {
                return handleProcessTransaction($postData);
            }
            
            // Dispatch to command
            return $dispatcher->dispatch($action, $postData);
        }
    }
    
    return null;
}

function logCommandExecution(TransactionResult $result): void
{
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['wa_current_user']->username ?? 'unknown',
        'status' => $result->isSuccess() ? 'success' : ($result->isError() ? 'error' : 'warning'),
        'message' => $result->getMessage(),
        'data' => $result->getData()
    ];
    
    error_log(json_encode($logData), 3, '/path/to/bank_import.log');
}

function refreshAjaxComponents($Ajax, TransactionResult $result): void
{
    if (!$Ajax) {
        return;
    }
    
    // Always refresh transaction table
    $Ajax->activate('doc_tbl');
    
    // Refresh customer/vendor lists if entities were created
    if ($result->getData('created')) {
        $Ajax->activate('customer_select');
        $Ajax->activate('vendor_select');
    }
}

function handleProcessTransaction(array $postData): TransactionResult
{
    // Keep existing TransactionProcessor logic
    global $transactionProcessor;
    
    foreach ($postData['ProcessTransaction'] as $key => $value) {
        $data = extractTransactionData($key, $value, $postData);
        return $transactionProcessor->process(
            $data['import_id'],
            $data['trans_type'],
            $data['trans_no'],
            $data['dc'],
            $data['qe_id']
        );
    }
    
    return TransactionResult::error('No transaction to process');
}

// ============================================================================
// EXAMPLE 4: Backward Compatibility Layer
// ============================================================================

/**
 * Gradual migration approach - Keep old code while testing new
 *
 * Use feature flag to toggle between old and new implementations
 */
function example4_backward_compatibility(): void
{
    global $container, $bi_controller, $Ajax;
    
    // Feature flag (set in config or database)
    $useCommandPattern = defined('USE_COMMAND_PATTERN') && USE_COMMAND_PATTERN;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($useCommandPattern) {
            // NEW IMPLEMENTATION
            $commandDispatcher = new CommandDispatcher($container);
            $result = handlePostAction($commandDispatcher, $_POST);
            
            if ($result !== null) {
                $result->display();
                if ($Ajax) {
                    $Ajax->activate('doc_tbl');
                }
            }
        } else {
            // OLD IMPLEMENTATION (keep during migration)
            handlePostActionsLegacy($bi_controller, $Ajax);
        }
    }
}

function handlePostActionsLegacy($bi_controller, $Ajax): void
{
    // This is the OLD code from lines 100-130
    // Keep it during migration for safety
    
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
// HELPER FUNCTIONS
// ============================================================================

function verifyCsrfToken(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function hasPermission(string $permission): bool
{
    global $security_areas;
    return check_user_access($permission);
}

function shouldRedirect(): bool
{
    // Implement PRG (Post-Redirect-Get) pattern logic
    return !isAjaxRequest();
}

function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function extractTransactionData(string $key, $value, array $postData): array
{
    // Extract transaction processing data from POST
    $parts = explode('-', $key);
    
    return [
        'import_id' => (int)($parts[0] ?? 0),
        'trans_type' => (int)($_POST['trans_type'][$key] ?? 0),
        'trans_no' => (int)($_POST['trans_no'][$key] ?? 0),
        'dc' => $_POST['dc'][$key] ?? 'D',
        'qe_id' => (int)($_POST['qe_id'][$key] ?? 0)
    ];
}

// ============================================================================
// ACTUAL REFACTORED CODE TO USE IN process_statements.php
// ============================================================================

/**
 * This is the actual code to replace lines 100-130 in process_statements.php
 *
 * STEP 1: Add this near line 30 (after includes, before HTML)
 */
function actualRefactor_step1_initialization(): void
{
    // Initialize command dispatcher
    global $container;
    
    if (!isset($container)) {
        // Create simple container if none exists
        $container = new class {
            private array $instances = [];
            
            public function bind(string $class, $instance): void {
                $this->instances[$class] = $instance;
            }
            
            public function make(string $class, array $params = []) {
                if (isset($this->instances[$class])) {
                    return $this->instances[$class];
                }
                
                // Simple reflection-based instantiation
                $reflector = new ReflectionClass($class);
                if ($reflector->getConstructor()) {
                    return $reflector->newInstanceArgs(array_values($params));
                }
                return $reflector->newInstance();
            }
        };
        
        // Bind repositories and services
        $container->bind('TransactionRepository', $bi_transactions_model);
        // Add more bindings as needed
    }
    
    $commandDispatcher = new CommandDispatcher($container);
}

/**
 * STEP 2: Replace lines 100-130 with this
 */
function actualRefactor_step2_replacePostHandling(): void
{
    global $commandDispatcher, $Ajax;
    
    // Handle POST actions using Command Pattern
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
        
        foreach ($actions as $action) {
            if (isset($_POST[$action])) {
                $result = $commandDispatcher->dispatch($action, $_POST);
                $result->display();
                
                if ($Ajax) {
                    $Ajax->activate('doc_tbl');
                }
                break; // Only one action per request
            }
        }
    }
}

// ============================================================================
// MIGRATION CHECKLIST
// ============================================================================

/**
 * MIGRATION STEPS:
 *
 * 1. ✅ Create CommandInterface and CommandDispatcherInterface
 * 2. ✅ Create CommandDispatcher class
 * 3. ✅ Create command classes (UnsetTransaction, AddCustomer, etc.)
 * 4. ✅ Write unit tests for all commands
 * 5. ⏳ Initialize dispatcher in process_statements.php
 * 6. ⏳ Replace procedural POST handling with dispatcher calls
 * 7. ⏳ Test each action manually
 * 8. ⏳ Run automated tests
 * 9. ⏳ Deploy with feature flag
 * 10. ⏳ Monitor in production
 * 11. ⏳ Remove legacy code after successful migration
 * 12. ⏳ Delete deprecated bi_controller methods
 *
 * ROLLBACK PLAN:
 * - Keep USE_COMMAND_PATTERN = false in config
 * - Old code remains functional
 * - Can switch back instantly if issues found
 */
