<?php
require 'vendor/autoload.php';

try {
    $m = Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping::create('bank', 'acct', 'intui', 'USD', 123);
    echo "✓ Method exists and works!\n";
    echo "FA Account ID: " . $m->getFAAccountId() . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
