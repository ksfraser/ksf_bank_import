<?php
require_once 'vendor/autoload.php';

$className = 'Ksfraser\\FaBankImport\\Shared\\Entities\\BankAccountMapping';
echo "Class exists: " . (class_exists($className) ? 'YES' : 'NO') . "\n";

if (class_exists($className)) {
    $rc = new ReflectionClass($className);
    $methods = [];
    foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $m) {
        $methods[] = $m->getName();
    }
    echo "Public static methods: " . implode(', ', $methods) . "\n";
}
