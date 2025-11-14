<?php
/**
 * Simple validation test - just checks class syntax
 */

echo "Checking HtmlEmail.php syntax...\n";
$output = shell_exec('php -l Views/HTML/HtmlEmail.php 2>&1');
echo $output;

echo "\nChecking HtmlA.php syntax...\n";
$output = shell_exec('php -l Views/HTML/HtmlA.php 2>&1');
echo $output;

echo "\n✓ Syntax checks complete!\n";
echo "\nNow check the class documentation:\n\n";

echo "=== HtmlEmail Constructor ===\n";
echo file_get_contents('Views/HTML/HtmlEmail.php');

echo "\n\n=== HtmlA Constructor ===\n";
echo file_get_contents('Views/HTML/HtmlA.php');
