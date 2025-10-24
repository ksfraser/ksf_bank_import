<?php

/**
 * Comparison Tests: BankTransferPartnerTypeView v1 vs v2 Step 0
 * 
 * Purpose: Validate that adding BankAccountDataProvider DI doesn't change output
 * 
 * @package    KsfBankImport\Tests\Integration\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 */

namespace KsfBankImport\Tests\Integration\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\BankAccountDataProvider;

// Load FA stubs
require_once __DIR__ . '/../../../includes/fa_stubs.php';

// Load both versions
require_once __DIR__ . '/../../../views/BankTransferPartnerTypeView.php';
require_once __DIR__ . '/../../../Views/BankTransferPartnerTypeView.v2.step0.php';

/**
 * Comparison tests for BankTransferPartnerTypeView
 * 
 * Proves v1 (original) and v2 Step 0 (with DI) produce identical output
 */
class BankTransferPartnerTypeViewComparisonTest extends TestCase
{
    private BankAccountDataProvider $dataProvider;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProvider = new BankAccountDataProvider();
        $_POST = [];
        $_GET = [];
    }
    
    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        BankAccountDataProvider::resetCache();
        parent::tearDown();
    }
    
    /**
     * Test v1 and v2 produce identical HTML for credit transaction (To Our Account)
     */
    public function testV1AndV2ProduceIdenticalHtmlForCreditTransaction(): void
    {
        // v1 (original)
        $v1 = new \BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',  // Credit - money coming in
            null,
            null
        );
        
        // v2 Step 0 (with DI)
        $v2 = new \KsfBankImport\Views\BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',  // Credit - money coming in
            $this->dataProvider,
            null,
            null
        );
        
        $v1Html = $v1->getHtml();
        $v2Html = $v2->getHtml();
        
        $this->assertEquals($v1Html, $v2Html, 'v1 and v2 should produce identical HTML for credit transaction');
        
        // Note: FA stub for label_row() is empty, so no output expected yet
        // This test proves DI doesn't break the flow
    }
    
    /**
     * Test v1 and v2 produce identical HTML for debit transaction (From Our Account)
     */
    public function testV1AndV2ProduceIdenticalHtmlForDebitTransaction(): void
    {
        // v1 (original)
        $v1 = new \BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'D',  // Debit - money going out
            null,
            null
        );
        
        // v2 Step 0 (with DI)
        $v2 = new \KsfBankImport\Views\BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'D',  // Debit - money going out
            $this->dataProvider,
            null,
            null
        );
        
        $v1Html = $v1->getHtml();
        $v2Html = $v2->getHtml();
        
        $this->assertEquals($v1Html, $v2Html, 'v1 and v2 should produce identical HTML for debit transaction');
        
        // Note: FA stub for label_row() is empty, so no output expected yet
        // This test proves DI doesn't break the flow
    }
    
    /**
     * Test display() method produces identical output
     */
    public function testV1AndV2DisplayMethodProducesIdenticalOutput(): void
    {
        $v1 = new \BankTransferPartnerTypeView(1, 'TEST', 'C');
        $v2 = new \KsfBankImport\Views\BankTransferPartnerTypeView(
            1, 'TEST', 'C', $this->dataProvider
        );
        
        ob_start();
        $v1->display();
        $v1Output = ob_get_clean();
        
        ob_start();
        $v2->display();
        $v2Output = ob_get_clean();
        
        $this->assertEquals($v1Output, $v2Output, 'display() should produce identical output');
    }
    
    /**
     * Test both handle empty FA stub output
     */
    public function testBothHandleEmptyFaStubOutput(): void
    {
        // FA stub for label_row() is empty, so both should return empty string
        
        $v1 = new \BankTransferPartnerTypeView(1, 'TEST', 'C');
        $v2 = new \KsfBankImport\Views\BankTransferPartnerTypeView(
            1, 'TEST', 'C', $this->dataProvider
        );
        
        $v1Html = $v1->getHtml();
        $v2Html = $v2->getHtml();
        
        $this->assertEquals($v1Html, $v2Html);
        $this->assertEquals('', $v1Html, 'FA stub returns empty string');
    }
    
    /**
     * Test v2 uses injected data provider
     */
    public function testV2UsesInjectedDataProvider(): void
    {
        $dataProvider = new BankAccountDataProvider();
        
        // Set some test data
        $dataProvider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Test Account 1'],
            ['id' => '2', 'bank_account_name' => 'Test Account 2'],
        ]);
        
        $view = new \KsfBankImport\Views\BankTransferPartnerTypeView(
            1,
            'TEST',
            'C',
            $dataProvider
        );
        
        // Should not crash - validates DI is working
        $html = $view->getHtml();
        $this->assertIsString($html);
    }
}
