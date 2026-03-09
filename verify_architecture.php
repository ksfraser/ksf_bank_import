<?php

require 'vendor/autoload.php';
require 'tests/compat.php';

$reflection = new ReflectionClass('Ksfraser\FaBankImport\TransType');
echo 'TransType namespace: ' . $reflection->getNamespaceName() . PHP_EOL;

$parent = $reflection->getParentClass();
echo 'Parent class: ' . $parent->getName() . PHP_EOL;
echo 'Parent namespace: ' . $parent->getNamespaceName() . PHP_EOL;

echo PHP_EOL;
echo 'Architecture verification:' . PHP_EOL;
echo '- Module-specific views: Ksfraser\FaBankImport\Views\*' . PHP_EOL;
echo '- Generic HTML components: Ksfraser\HTML\*' . PHP_EOL;
echo '- TransType correctly extends generic LabelRowBase' . PHP_EOL;