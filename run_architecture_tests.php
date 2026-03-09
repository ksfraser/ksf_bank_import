<?php

require 'vendor/autoload.php';
require 'tests/compat.php';
require 'tests/unit/ArchitectureTestSuite.php';

$test = new ArchitectureTestSuite();
$success = $test->runAllTests();

exit($success ? 0 : 1);