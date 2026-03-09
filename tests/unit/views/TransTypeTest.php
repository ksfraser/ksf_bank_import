<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\TransType;

/**
 * Test TransType view switch statement coverage and input validation
 *
 * Covers the critical gap in switch/case testing identified in coverage analysis.
 * Tests all code paths in the transactionDC switch statement.
 */
class TransTypeTest extends TestCase
{
    /**
     * Create a mock bi_lineitem object for testing
     */
    private function createMockLineitem($transactionDC = 'D'): object
    {
        $mock = new \stdClass();
        $mock->transactionDC = $transactionDC;

        return $mock;
    }

    /**
     * Test Credit transaction type (transactionDC = 'C')
     * Covers switch case 'C'
     */
    public function testCreditTransactionType(): void
    {
        $bi_lineitem = $this->createMockLineitem('C');
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Trans Type:', $html);
        $this->assertStringContainsString('Credit', $html);
        $this->assertStringNotContainsString('Debit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test Bank Transfer transaction type (transactionDC = 'B')
     * Covers switch case 'B'
     */
    public function testBankTransferTransactionType(): void
    {
        $bi_lineitem = $this->createMockLineitem('B');
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Trans Type:', $html);
        $this->assertStringContainsString('Bank Transfer', $html);
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Debit', $html);
    }

    /**
     * Test Debit transaction type (transactionDC = 'D')
     * Covers switch case 'D'
     */
    public function testDebitTransactionType(): void
    {
        $bi_lineitem = $this->createMockLineitem('D');
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Trans Type:', $html);
        $this->assertStringContainsString('Debit', $html);
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test default case with invalid transactionDC value
     * Covers switch default case
     */
    public function testInvalidTransactionTypeDefaultsToDebit(): void
    {
        $bi_lineitem = $this->createMockLineitem('X'); // Invalid value
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Trans Type:', $html);
        $this->assertStringContainsString('Debit', $html); // Should default to Debit
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test null transactionDC defaults to Debit
     * Covers default case with null input
     */
    public function testNullTransactionTypeDefaultsToDebit(): void
    {
        $bi_lineitem = $this->createMockLineitem(null);
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Debit', $html);
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test empty string transactionDC defaults to Debit
     * Covers default case with empty string
     */
    public function testEmptyTransactionTypeDefaultsToDebit(): void
    {
        $bi_lineitem = $this->createMockLineitem('');
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Debit', $html);
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test numeric transactionDC defaults to Debit
     * Covers default case with numeric input
     */
    public function testNumericTransactionTypeDefaultsToDebit(): void
    {
        $bi_lineitem = $this->createMockLineitem(123);
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Debit', $html);
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test case sensitivity - lowercase should default to Debit
     * Covers case sensitivity of switch statement
     */
    public function testLowercaseTransactionTypeDefaultsToDebit(): void
    {
        $bi_lineitem = $this->createMockLineitem('c'); // lowercase
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Debit', $html); // Case sensitive, should default
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test missing transactionDC property
     * Covers case where property doesn't exist
     */
    public function testMissingTransactionDCProperty(): void
    {
        $bi_lineitem = (object) []; // No transactionDC property
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();
        $this->assertStringContainsString('Debit', $html); // Should default to Debit
        $this->assertStringNotContainsString('Credit', $html);
        $this->assertStringNotContainsString('Bank Transfer', $html);
    }

    /**
     * Test HTML output structure and completeness
     */
    public function testHtmlOutputStructure(): void
    {
        $bi_lineitem = $this->createMockLineitem('C');
        $view = new TransType($bi_lineitem);

        $html = $view->getHtml();

        // Should contain proper HTML structure
        $this->assertStringContainsString('Trans Type:', $html);
        $this->assertStringContainsString('Credit', $html);

        // Should not contain the raw transactionDC value
        $this->assertStringNotContainsString('C', $html); // Should show 'Credit', not 'C'

        // Should be non-empty
        $this->assertNotEmpty($html);
        $this->assertIsString($html);
    }

    /**
     * Test that view implements HtmlElementInterface
     */
    public function testImplementsHtmlElementInterface(): void
    {
        $bi_lineitem = $this->createMockLineitem('C');
        $view = new TransType($bi_lineitem);

        $this->assertInstanceOf(\Ksfraser\HTML\HtmlElementInterface::class, $view);
        $this->assertIsString($view->getHtml());
    }

    /**
     * Test toHtml() method outputs directly
     */
    public function testToHtmlOutputsDirectly(): void
    {
        $bi_lineitem = $this->createMockLineitem('B');
        $view = new TransType($bi_lineitem);

        ob_start();
        $view->toHtml();
        $output = ob_get_clean();

        $this->assertStringContainsString('Bank Transfer', $output);
        $this->assertStringContainsString('Trans Type:', $output);
        $this->assertNotEmpty($output);
    }

    /**
     * Test all valid transaction types return expected labels
     */
    public function testAllValidTransactionTypes(): void
    {
        $testCases = [
            'C' => 'Credit',
            'B' => 'Bank Transfer',
            'D' => 'Debit'
        ];

        foreach ($testCases as $code => $expectedLabel) {
            $bi_lineitem = $this->createMockLineitem($code);
            $view = new TransType($bi_lineitem);
            $html = $view->getHtml();

            $this->assertStringContainsString($expectedLabel, $html,
                "Transaction code '$code' should display '$expectedLabel'");
        }
    }

    /**
     * Test that invalid values all default to Debit
     */
    public function testInvalidValuesDefaultToDebit(): void
    {
        $invalidValues = ['X', 'Y', 'Z', '1', 'ABC', '', null, 0, false, []];

        foreach ($invalidValues as $invalidValue) {
            $bi_lineitem = $this->createMockLineitem($invalidValue);
            $view = new TransType($bi_lineitem);
            $html = $view->getHtml();

            $this->assertStringContainsString('Debit', $html,
                "Invalid value '" . var_export($invalidValue, true) . "' should default to Debit");
        }
    }
}
