<?php
require 'vendor/autoload.php';

use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\Composites\HTML_ROW;

echo "Testing HTML_ROW_LABEL wrapper:\n";
echo "================================\n\n";

// Test 1: Basic usage matching old code
$row1 = new HTML_ROW_LABEL('John Doe', 'Username:', 25, 'label');
echo "Test 1 - Basic:\n";
echo $row1->getHtml();
echo "\n\n";

// Test 2: Custom width and class
$row2 = new HTML_ROW_LABEL('admin@example.com', 'Email:', 30, 'custom-label');
echo "Test 2 - Custom width/class:\n";
echo $row2->getHtml();
echo "\n\n";

// Test 3: With null parameters (should use defaults)
$row3 = new HTML_ROW_LABEL('Active', 'Status:',  null, null);
echo "Test 3 - Null parameters (defaults):\n";
echo $row3->getHtml();
echo "\n\n";

echo "Testing HTML_ROW wrapper:\n";
echo "==========================\n\n";

$row4 = new HTML_ROW('<td>Cell 1</td><td>Cell 2</td>');
echo "Test 4 - HTML_ROW:\n";
echo $row4->getHtml();
echo "\n\n";

echo "All tests completed!\n";
