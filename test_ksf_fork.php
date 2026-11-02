#!/usr/bin/php -f
<?php
/**
 * Simple test script to verify ksf_fork parser works with production QFX files
 * 
 * Usage: php test_ksf_fork.php <qfx_file>
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config (should be set to 'ksf_fork')
require_once __DIR__ . '/config/ofx_parser_config.php';

echo "=== KSF Fork Parser Test ===\n";
echo "Using OFX Parser Version: " . OFX_PARSER_VERSION . "\n";
echo "Autoload Path: " . OFX_PARSER_AUTOLOAD . "\n\n";

// Get test file from command line or use default
$test_file = $argv[1] ?? __DIR__ . '/includes/test.qfx';

if (!file_exists($test_file)) {
    die("ERROR: Test file not found: $test_file\n");
}

echo "Test File: $test_file\n";
echo "File Size: " . number_format(filesize($test_file)) . " bytes\n\n";

// Load the parser
require_once __DIR__ . '/includes/qfx_parser.php';

try {
    echo "Parsing file...\n";
    $parser = new qfx_parser();
    
    // Set up minimal static_data for the parser
    $static_data = [
        'account' => '2992',
        'account_number' => '2992',
        'currency' => 'CAD',
        'account_code' => 1061,
        'account_type' => 'CHECKING',  // Use string format
        'account_name' => 'TEST ACCOUNT',
        'bank_charge_act' => 5690
    ];
    
    $content = file_get_contents($test_file);
    
    echo "Content Length: " . strlen($content) . " bytes\n";
    echo "First 200 chars:\n" . substr($content, 0, 200) . "\n\n";
    
    $result = $parser->parse($content, $static_data);
    
    if ($result === false) {
        echo "ERROR: Parser returned false\n";
        if (isset($parser->error)) {
            echo "Parser Error: " . $parser->error . "\n";
        }
    } else {
        echo "SUCCESS: Parsing completed!\n";
        echo "Transactions found: " . count($result) . "\n\n";
        
        if (count($result) > 0) {
            echo "First transaction:\n";
            print_r($result[0]);
        }
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
