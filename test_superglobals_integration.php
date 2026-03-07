<?php
require_once __DIR__ . "/vendor/autoload.php";

echo "Testing Superglobals Integration...\n";

// Test 1: FormSubmission from library
echo "1. Testing FormSubmission...\n";
$form = new Ksfraser\Superglobals\FormSubmission(
    new Ksfraser\Superglobals\PostParameterProvider()
);
echo "   ✓ FormSubmission instantiated\n";

// Test 2: ParameterProvider implementations
echo "2. Testing ParameterProvider implementations...\n";
$postProvider = new Ksfraser\Superglobals\PostParameterProvider();
$getProvider = new Ksfraser\Superglobals\GetParameterProvider();
echo "   ✓ PostParameterProvider instantiated\n";
echo "   ✓ GetParameterProvider instantiated\n";

// Test 3: ParserSelector (application-specific)
echo "3. Testing ParserSelector...\n";
class MockParserRegistry {
    public function getParsersArray(): array {
        return ["QFX" => ["name" => "QFX Parser"], "OFX" => ["name" => "OFX Parser"]];
    }
}

$selector = new Ksfraser\FaBankImport\Request\ParserSelector(
    $postProvider,
    new MockParserRegistry()
);
echo "   ✓ ParserSelector instantiated\n";

// Test 4: Integration test
echo "4. Testing integration...\n";
// Simulate POST data
$_POST['parser'] = 'QFX';
$_POST['upload'] = '1';
$_POST['bank_account'] = '123';

$form = new Ksfraser\Superglobals\FormSubmission($postProvider);
assert($form->getParser() === 'QFX', 'Parser should be QFX');
assert($form->hasUpload() === true, 'Should have upload');
assert($form->getBankAccount() === '123', 'Bank account should be 123');

$selector = new Ksfraser\FaBankImport\Request\ParserSelector($postProvider, new MockParserRegistry());
assert($selector->getSelectedParser() === 'QFX', 'Should select QFX parser');

echo "   ✓ Integration test passed\n";

// Clean up
unset($_POST['parser'], $_POST['upload'], $_POST['bank_account']);

echo "\n✅ All Superglobals Integration Tests Passed!\n";
echo "The ksfraser/superglobals library is working correctly with the application.\n";