<?php
/**
 * Quick test for nested link validation
 */

require_once __DIR__ . '/Views/HTML/HtmlElementInterface.php';
require_once __DIR__ . '/Views/HTML/HtmlElement.php';
require_once __DIR__ . '/Views/HTML/HtmlString.php';
require_once __DIR__ . '/Views/HTML/HtmlRawString.php';
require_once __DIR__ . '/Views/HTML/HtmlAttribute.php';
require_once __DIR__ . '/Views/HTML/HtmlLink.php';
require_once __DIR__ . '/Views/HTML/HtmlA.php';
require_once __DIR__ . '/Views/HTML/HtmlEmail.php';

use Ksfraser\HTML\HTMLAtomic\{HtmlA, HtmlEmail, HtmlString};

echo "=== Testing Nested Link Prevention ===\n\n";

// Test 1: Valid - string content
echo "1. Valid: String content\n";
try {
    $link = new HtmlA("https://example.com", "Click Here");
    echo "✓ Success: " . $link->getHtml() . "\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Valid - HtmlString content
echo "2. Valid: HtmlString content\n";
try {
    $link = new HtmlA("https://example.com", new HtmlString("Click Here"));
    echo "✓ Success: " . $link->getHtml() . "\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Valid - null content
echo "3. Valid: Null content (uses URL)\n";
try {
    $link = new HtmlA("https://example.com");
    echo "✓ Success: " . $link->getHtml() . "\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: INVALID - nested HtmlA
echo "4. Invalid: Nested HtmlA (should fail)\n";
try {
    $innerLink = new HtmlA("https://inner.com", "Inner");
    $outerLink = new HtmlA("https://outer.com", $innerLink);
    echo "✗ ERROR: Should have thrown exception!\n\n";
} catch (Exception $e) {
    echo "✓ Correctly rejected: " . $e->getMessage() . "\n\n";
}

// Test 5: INVALID - nested HtmlEmail
echo "5. Invalid: Nested HtmlEmail (should fail)\n";
try {
    $emailLink = new HtmlEmail("test@example.com", "Email");
    $outerLink = new HtmlA("https://outer.com", $emailLink);
    echo "✗ ERROR: Should have thrown exception!\n\n";
} catch (Exception $e) {
    echo "✓ Correctly rejected: " . $e->getMessage() . "\n\n";
}

// Test 6: INVALID - HtmlEmail containing HtmlA
echo "6. Invalid: HtmlEmail containing HtmlA (should fail)\n";
try {
    $innerLink = new HtmlA("https://example.com", "Click");
    $emailLink = new HtmlEmail("test@example.com", $innerLink);
    echo "✗ ERROR: Should have thrown exception!\n\n";
} catch (Exception $e) {
    echo "✓ Correctly rejected: " . $e->getMessage() . "\n\n";
}

echo "=== All Validation Tests Complete ===\n";
