<?php
/**
 * Manual test for HtmlEmail and HtmlA robustness improvements
 * Run with: php test_html_links_manual.php
 */

require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlElementInterface.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlElement.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlString.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlRaw.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlAttribute.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlLink.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlEmail.php';
require_once __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlA.php';

use Ksfraser\HTML\Elements\{HtmlEmail, HtmlA, HtmlString};
use Ksfraser\HTML\Elements\HtmlRaw;

echo "=== Testing HtmlEmail ===\n\n";

// Test 1: Email with HtmlString content
echo "1. Email with HtmlString content:\n";
$email1 = new HtmlEmail("test@example.com", new HtmlString("Email Me"));
echo $email1->getHtml() . "\n\n";

// Test 2: Email with plain string content
echo "2. Email with plain string (auto-wrapped):\n";
$email2 = new HtmlEmail("info@company.com", "Contact Us");
echo $email2->getHtml() . "\n\n";

// Test 3: Email with null content (uses email as text)
echo "3. Email with null content (email as link text):\n";
$email3 = new HtmlEmail("support@example.com");
echo $email3->getHtml() . "\n\n";

// Test 4: Email with params (subject, body)
echo "4. Email with query parameters:\n";
$email4 = new HtmlEmail("help@example.com", "Get Help");
$email4->addParam("subject", "Support Request");
$email4->addParam("body", "I need help with...");
echo $email4->getHtml() . "\n\n";

// Test 5: Email with validation disabled
echo "5. Email with validation disabled:\n";
$email5 = new HtmlEmail("custom-format", "Custom", false);
echo $email5->getHtml() . "\n\n";

echo "=== Testing HtmlA ===\n\n";

// Test 6: Link with HtmlString content
echo "6. Link with HtmlString content:\n";
$link1 = new HtmlA("https://example.com", new HtmlString("Visit Site"));
echo $link1->getHtml() . "\n\n";

// Test 7: Link with plain string content
echo "7. Link with plain string (auto-wrapped):\n";
$link2 = new HtmlA("https://google.com", "Search");
echo $link2->getHtml() . "\n\n";

// Test 8: Link with null content (uses URL as text)
echo "8. Link with null content (URL as link text):\n";
$link3 = new HtmlA("https://github.com");
echo $link3->getHtml() . "\n\n";

// Test 9: Link with RawString (HTML content)
echo "9. Link with raw HTML content:\n";
$link4 = new HtmlA("/page", new HtmlRaw("<strong>Bold</strong> Link"));
echo $link4->getHtml() . "\n\n";

// Test 10: Link with params and target
echo "10. Link with query params and target:\n";
$link5 = new HtmlA("/search", "Search Results");
$link5->addParam("q", "test query");
$link5->addParam("page", "2");
$link5->setTarget("_blank");
echo $link5->getHtml() . "\n\n";

echo "=== Testing Error Handling ===\n\n";

// Test 11: Invalid email
echo "11. Invalid email (should throw exception):\n";
try {
    $badEmail = new HtmlEmail("not-an-email", "Click");
    echo "ERROR: Should have thrown exception!\n";
} catch (Exception $e) {
    echo "✓ Caught: " . $e->getMessage() . "\n\n";
}

// Test 12: Invalid content type for Email
echo "12. Invalid content type for Email (should throw exception):\n";
try {
    $badEmail = new HtmlEmail("test@example.com", 123);
    echo "ERROR: Should have thrown exception!\n";
} catch (Exception $e) {
    echo "✓ Caught: " . $e->getMessage() . "\n\n";
}

// Test 13: Invalid content type for HtmlA
echo "13. Invalid content type for HtmlA (should throw exception):\n";
try {
    $badLink = new HtmlA("https://example.com", ['array']);
    echo "ERROR: Should have thrown exception!\n";
} catch (Exception $e) {
    echo "✓ Caught: " . $e->getMessage() . "\n\n";
}

echo "=== All Tests Complete ===\n";

