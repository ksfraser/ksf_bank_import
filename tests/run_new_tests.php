<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';

echo "Running FormSubmissionTest...\n";
$test = new FormSubmissionTest();
$test->testHasUploadReturnsTrueWhenUploadPresent();
$test->testHasUploadReturnsFalseWhenUploadNotPresent();
$test->testHasImportReturnsTrueWhenImportPresent();
$test->testHasImportReturnsFalseWhenImportNotPresent();
$test->testGetStateReturnsStateValue();
$test->testGetParserReturnsParserValue();
$test->testGetBankAccountReturnsBankAccountValue();
echo "FormSubmissionTest passed!\n";

echo "Running ParameterProviderTest...\n";
$test = new ParameterProviderTest();
$test->testPostParameterProviderGetsExistingValue();
$test->testPostParameterProviderReturnsNullForNonExistingValue();
$test->testGetParameterProviderGetsExistingValue();
$test->testGetParameterProviderReturnsNullForNonExistingValue();
echo "ParameterProviderTest passed!\n";

echo "Running ParserSelectorTest...\n";
$test = new ParserSelectorTest();
$test->testGetSelectedParserReturnsParserFromParameterProvider();
$test->testGetSelectedParserReturnsDefaultWhenNoParserSelected();
$test->testGetSelectedParserReturnsFirstParserWhenQFXNotAvailable();
echo "ParserSelectorTest passed!\n";

echo "All tests passed!\n";