<?php
echo "Testing class autoloading...\n";

// Step 1: Require autoloader
require __DIR__ . '/vendor/autoload.php';
echo "✓ Autoloader loaded\n";

// Step 2: Check if FA constants file exists
if (file_exists(__DIR__ . '/includes/fa_stubs.php')) {
    require_once __DIR__ . '/includes/fa_stubs.php';
    echo "✓ FA stubs loaded\n";
} else {
    echo "⚠ FA stubs not found at includes/fa_stubs.php\n";
}

// Step 3: Check for parent class
if (class_exists('generic_fa_interface_model', false)) {
    echo "✓ generic_fa_interface_model found\n";
} else {
    echo "✗ generic_fa_interface_model NOT found\n";
    // Try to find where it is
    $candidates = [
        __DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php',
        __DIR__ . '/ksf_modules_common/class.generic_fa_interface.php',
        __DIR__ . '/src/ksf_modules_common/class.generic_fa_interface.php',
    ];
    foreach ($candidates as $file) {
        if (file_exists($file)) {
            echo "  Found at: $file\n";
        }
    }
}

// Step 4: Try to autoload bi_transactions_model
if (class_exists('bi_transactions_model', true)) {
    echo "✓ bi_transactions_model autoloaded successfully\n";
} else {
    echo "✗ bi_transactions_model FAILED to autoload\n";
}

// Step 5: Try to autoload bi_transaction
if (class_exists('bi_transaction', true)) {
    echo "✓ bi_transaction autoloaded successfully\n";
} else {
    echo "✗ bi_transaction FAILED to autoload\n";
}

echo "\nDone.\n";
