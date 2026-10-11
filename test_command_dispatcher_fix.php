<?php
/**
 * Simple test to verify CommandDispatcher can be instantiated
 * after moving bi_transactions_model before command_bootstrap
 */

// Mock the required FA environment
$path_to_root = __DIR__;

// Include the bi_transactions class
require_once('class.bi_transactions.php');

// Create the bi_transactions_model instance (this was moved earlier)
$bit = new bi_transactions_model();
echo "bi_transactions_model instantiated successfully\n";

// Now include command_bootstrap (this should work now)
require_once(__DIR__ . '/src/Ksfraser/FaBankImport/command_bootstrap.php');
echo "command_bootstrap included successfully\n";

// Check if CommandDispatcher was created
if (isset($commandDispatcher)) {
    echo "CommandDispatcher instantiated successfully\n";
    echo "Type: " . get_class($commandDispatcher) . "\n";
} else {
    echo "CommandDispatcher was not instantiated\n";
}

// Check if container has the repository bound
if (isset($container)) {
    try {
        $repo = $container->make('TransactionRepository');
        echo "TransactionRepository resolved successfully: " . get_class($repo) . "\n";
    } catch (Exception $e) {
        echo "TransactionRepository resolution failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "Container not found\n";
}

echo "Test completed\n";