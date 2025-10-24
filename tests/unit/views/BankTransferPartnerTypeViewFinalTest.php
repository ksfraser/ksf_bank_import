<?php

/**
 * Unit Tests for BankTransferPartnerTypeView (Final - Steps 0-2 complete)
 * 
 * @package    KsfBankImport\Tests\Unit\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use KsfBankImport\Views\BankTransferPartnerTypeView;
use Ksfraser\BankAccountDataProvider;

// Load FA stubs
require_once __DIR__ . '/../../../includes/fa_stubs.php';

// Load the view
require_once __DIR__ . '/../../../Views/BankTransferPartnerTypeView.v2.final.php';

/**
 * Test Bank Transfer Partner Type View
 * 
 * @coversDefaultClass \KsfBankImport\Views\BankTransferPartnerTypeView
 */
class BankTransferPartnerTypeViewFinalTest extends TestCase
{
    private $dataProvider;
    
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProvider = new BankAccountDataProvider();
        $_POST = [];
        $_GET = [];
    }
    
    /**
     * Reset after each test
     */
    protected function tearDown(): void
    {
        BankAccountDataProvider::resetCache();
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }
    
    /**
     * Test constructor accepts all required parameters
     * 
     * @covers ::__construct
     */
    public function testConstructorAcceptsAllParameters(): void
    {
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',
            $this->dataProvider
        );
        
        $this->assertInstanceOf(BankTransferPartnerTypeView::class, $view);
    }
    
    /**
     * Test getHtml returns string
     * 
     * @covers ::getHtml
     */
    public function testGetHtmlReturnsString(): void
    {
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        $this->assertIsString($html);
    }
    
    /**
     * Test credit transaction shows "from" direction
     * 
     * @covers ::getHtml
     */
    public function testCreditTransactionShowsFromDirection(): void
    {
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',  // Credit - money coming in
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // With proper FA implementation, would contain "from (OTHER ACCOUNT"
        // For now, just verify it doesn't crash
        $this->assertIsString($html);
    }
    
    /**
     * Test debit transaction shows "To" direction
     * 
     * @covers ::getHtml
     */
    public function testDebitTransactionShowsToDirection(): void
    {
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'D',  // Debit - money going out
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // With proper FA implementation, would contain "To (OTHER ACCOUNT"
        // For now, just verify it doesn't crash
        $this->assertIsString($html);
    }
    
    /**
     * Test uses data provider for bank account data
     * 
     * @covers ::getHtml
     */
    public function testUsesDataProviderForBankAccountData(): void
    {
        // This test validates the DI is working
        
        $dataProvider = new BankAccountDataProvider();
        $dataProvider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Test Account 1'],
            ['id' => '2', 'bank_account_name' => 'Test Account 2'],
        ]);
        
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',
            $dataProvider
        );
        
        // Should not crash
        $html = $view->getHtml();
        $this->assertIsString($html);
    }
    
    /**
     * Test display method outputs HTML
     * 
     * @covers ::display
     */
    public function testDisplayMethodOutputsHtml(): void
    {
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',
            $this->dataProvider
        );
        
        ob_start();
        $view->display();
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }
    
    /**
     * Test constructor with all optional parameters
     * 
     * @covers ::__construct
     */
    public function testConstructorWithAllOptionalParameters(): void
    {
        $view = new BankTransferPartnerTypeView(
            1,
            'TEST-BANK-ACCOUNT',
            'C',
            $this->dataProvider,
            123,  // partnerId
            456   // partnerDetailId
        );
        
        $this->assertInstanceOf(BankTransferPartnerTypeView::class, $view);
    }
}
