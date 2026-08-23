<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests that getLeftHtml() uses SRP View classes instead of label_row()
 * 
 * After refactoring, getLeftHtml() should use:
 * - TransDate instead of label_row("Trans Date...",...)
 * - TransType instead of label_row("Trans type:",...)
 * - OurBankAccount instead of label_row("Our Bank Account...",...)
 * - OtherBankAccount instead of label_row("Other account:",...)
 * - AmountCharges instead of label_row("Amount/Charge(s):",...)
 * - TransTitle instead of label_row("Trans Title:",...)
 */
class BiLineItemViewClassesTest extends TestCase
{
	/**
	 * Minimal transaction data required by bi_lineitem's constructor
	 */
	private function makeSampleTransaction(): array
	{
		return [
			'transactionDC' => 'C',
			'memo' => 'Test Transaction',
			'our_account' => '12345',
			'valueTimestamp' => '2025-12-19',
			'entryTimestamp' => '2025-12-19 10:00:00',
			'accountName' => 'Other Account',
			'transactionTitle' => 'Test Transaction',
			'transactionCode' => 'TC001',
			'transactionCodeDesc' => 'Test Code',
			'currency' => 'CAD',
			'status' => 0,
			'id' => 123,
			'fa_trans_type' => 0,
			'fa_trans_no' => 0,
			'transactionAmount' => 100.00,
			'transactionType' => 'TRN'
		];
	}

	/**
	 * Test that getLeftHtml() output contains expected View class content
	 */
	public function testGetLeftHtmlUsesViewClasses()
	{
		require_once __DIR__ . '/../../class.bi_lineitem.php';
		
		// Create test instance with minimal data
		$lineitem = new \bi_lineitem($this->makeSampleTransaction());
		$lineitem->valueTimestamp = '2025-12-19';
		$lineitem->entryTimestamp = '2025-12-19 10:00:00';
		$lineitem->transactionTypeLabel = 'Test Type';
		$lineitem->our_account = '12345';
		$lineitem->ourBankAccountName = 'Test Account';
		$lineitem->ourBankAccountCode = 'TA001';
		$lineitem->otherBankAccount = '67890';
		$lineitem->otherBankAccountName = 'Other Account';
		$lineitem->amount = 100.00;
		$lineitem->charge = 5.00;
		$lineitem->currency = 'CAD';
		$lineitem->transactionTitle = 'Test Transaction';
		
		// Get HTML output
		$html = $lineitem->getLeftHtml();
		
		// Verify key content from View classes is present
		$this->assertStringContainsString('Trans Date (Event Date):', $html, 
			'TransDate View class should render label');
		$this->assertStringContainsString($lineitem->valueTimestamp, $html,
			'TransDate View class should render date value');
		
		$this->assertStringContainsString('Trans Type:', $html,
			'TransType View class should render label');
		// TransType derives its label from transactionDC ('C' => Credit)
		$this->assertStringContainsString('Credit', $html,
			'TransType View class should render transaction type');
		
		$this->assertStringContainsString('Our Bank Account', $html,
			'OurBankAccount View class should render label');
		
		$this->assertStringContainsString('Other Bank Account:', $html,
			'OtherBankAccount View class should render label');
		$this->assertStringContainsString($lineitem->otherBankAccountName, $html,
			'OtherBankAccount View class should render other account name');
		
		$this->assertStringContainsString('Amount / Charge(s):', $html,
			'AmountCharges View class should render label');
		$this->assertStringContainsString((string)$lineitem->amount, $html,
			'AmountCharges View class should render amount');
		
		$this->assertStringContainsString('Transaction Title:', $html,
			'TransTitle View class should render label');
		$this->assertStringContainsString($lineitem->transactionTitle, $html,
			'TransTitle View class should render title');
	}
	
	/**
	 * Test that the refactored code doesn't call label_row directly
	 *
	 * View class instantiation now lives in getLeftTd(); getLeftHtml() delegates.
	 */
	public function testGetLeftHtmlMethodDoesNotCallLabelRowDirectly()
	{
		require_once __DIR__ . '/../../class.bi_lineitem.php';
		
		// Read the method source
		$reflection = new \ReflectionMethod('bi_lineitem', 'getLeftTd');
		$fileName = $reflection->getFileName();
		$startLine = $reflection->getStartLine();
		$endLine = $reflection->getEndLine();
		
		$fileContents = file($fileName);
		$methodSource = implode('', array_slice($fileContents, $startLine - 1, $endLine - $startLine + 1));
		
		// Verify no direct label_row() calls (should use View classes instead)
		$this->assertStringNotContainsString('label_row(', $methodSource,
			'getLeftHtml() should not call label_row() directly - should use View classes');
		
		// Verify View class instantiations are present
		$this->assertStringContainsString('new TransDate', $methodSource,
			'getLeftHtml() should instantiate TransDate View class');
		$this->assertStringContainsString('new TransType', $methodSource,
			'getLeftHtml() should instantiate TransType View class');
		$this->assertStringContainsString('new OurBankAccount', $methodSource,
			'getLeftHtml() should instantiate OurBankAccount View class');
		$this->assertStringContainsString('new OtherBankAccount', $methodSource,
			'getLeftHtml() should instantiate OtherBankAccount View class');
		$this->assertStringContainsString('new AmountCharges', $methodSource,
			'getLeftHtml() should instantiate AmountCharges View class');
		$this->assertStringContainsString('new TransTitle', $methodSource,
			'getLeftHtml() should instantiate TransTitle View class');
	}
	
	/**
	 * Test that output structure uses HTML table structure
	 */
	public function testGetLeftHtmlOutputStructure()
	{
		require_once __DIR__ . '/../../class.bi_lineitem.php';
		
		$lineitem = new \bi_lineitem($this->makeSampleTransaction());
		$lineitem->valueTimestamp = '2025-12-19';
		$lineitem->entryTimestamp = '2025-12-19 10:00:00';
		$lineitem->transactionTypeLabel = 'Test';
		$lineitem->our_account = '12345';
		$lineitem->ourBankAccountName = 'Test';
		$lineitem->ourBankAccountCode = 'T001';
		$lineitem->otherBankAccount = '67890';
		$lineitem->otherBankAccountName = 'Other';
		$lineitem->amount = 100.00;
		$lineitem->charge = 5.00;
		$lineitem->currency = 'CAD';
		$lineitem->transactionTitle = 'Test';
		
		$html = $lineitem->getLeftHtml();
		
		// Verify HTML structure
		$this->assertStringContainsString('<tr>', $html, 'Should contain table row');
		$this->assertStringContainsString('<td', $html, 'Should contain table cell');
		$this->assertStringContainsString('<table', $html, 'Should contain table');
		$this->assertStringContainsString('</table>', $html, 'Should close table');
	}
}
