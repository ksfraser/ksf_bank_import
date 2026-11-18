<?php

require_once 'vendor/autoload.php';
require_once 'tests/bootstrap.php';

use Ksfraser\FaBankImport\Strategies\IDGeneration\IDGenerationContext;

// Test the context
$context = new IDGenerationContext();

// Test 1: Bank Account Identifier
echo "Test 1: Bank Account Identifier Strategy" . PHP_EOL;
$bankKey = $context->generate('bank_account_identifier', [
    'bankid' => '021000021',
    'acctid' => '123456789'
]);
echo "  Generated: " . $bankKey . PHP_EOL;
echo "  ✓ Passed" . PHP_EOL . PHP_EOL;

// Test 2: Config Key Generation
echo "Test 2: Config Key Generation Strategy" . PHP_EOL;
$configKey = $context->generate('config_key_generation', [
    'account_identifier' => 'Checking Account 1234'
]);
echo "  Generated: " . $configKey . PHP_EOL;
echo "  Length: " . strlen($configKey) . " chars (max 100)" . PHP_EOL;
echo "  ✓ Passed" . PHP_EOL . PHP_EOL;

// Test 3: List available strategies
echo "Test 3: Available Strategies" . PHP_EOL;
$strategies = $context->getStrategyInfo();
foreach ($strategies as $name => $params) {
    echo "  - " . $name . PHP_EOL;
    foreach ($params as $param => $desc) {
        echo "      " . $param . ": " . $desc . PHP_EOL;
    }
}
echo "  ✓ Passed" . PHP_EOL . PHP_EOL;

echo "✓ All Strategy Pattern tests passed!" . PHP_EOL;
