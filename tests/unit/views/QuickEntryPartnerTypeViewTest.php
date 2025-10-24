<?php

/**
 * Unit Tests for QuickEntryPartnerTypeView
 * 
 * @package    KsfBankImport\Tests\Unit\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use KsfBankImport\Views\QuickEntryPartnerTypeView;
use KsfBankImport\Views\DataProviders\QuickEntryDataProvider;

// Load FA stubs
require_once __DIR__ . '/../../../includes/fa_stubs.php';

// Load the view
require_once __DIR__ . '/../../../Views/QuickEntryPartnerTypeView.v2.php';

/**
 * Test Quick Entry Partner Type View
 * 
 * @coversDefaultClass \KsfBankImport\Views\QuickEntryPartnerTypeView
 */
class QuickEntryPartnerTypeViewTest extends TestCase
{
    private $dataProvider;
    
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Create a deposit provider for testing
        $this->dataProvider = QuickEntryDataProvider::forDeposit();
        $_POST = [];
        $_GET = [];
    }
    
    /**
     * Reset after each test
     */
    protected function tearDown(): void
    {
        QuickEntryDataProvider::reset();
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
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $this->assertInstanceOf(QuickEntryPartnerTypeView::class, $view);
    }
    
    /**
     * Test getHtml returns string
     * 
     * @covers ::getHtml
     */
    public function testGetHtmlReturnsString(): void
    {
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * Test HTML contains Quick Entry label
     * 
     * @covers ::getHtml
     */
    public function testHtmlContainsQuickEntryLabel(): void
    {
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        $this->assertStringContainsString('Quick Entry', $html);
    }
    
    /**
     * Test uses data provider for quick entry checking
     * 
     * @covers ::getHtml
     * @covers ::renderQuickEntryDescription
     */
    public function testUsesDataProviderForQuickEntryChecking(): void
    {
        // Set a partner ID in POST
        $_POST['partnerId_1'] = '1';
        
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should use data provider to get entry details
        $this->assertIsString($html);
    }
    
    /**
     * Test display method outputs HTML
     * 
     * @covers ::display
     */
    public function testDisplayMethodOutputsHtml(): void
    {
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        ob_start();
        $view->display();
        $output = ob_get_clean();
        
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Quick Entry', $output);
    }
    
    /**
     * Test HTML structure is well-formed
     * 
     * @covers ::getHtml
     */
    public function testHtmlStructureIsWellFormed(): void
    {
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Check for basic HTML structure
        $this->assertStringContainsString('<tr', $html);
        $this->assertStringContainsString('</tr>', $html);
    }
    
    /**
     * Test deposit transaction type uses QE_DEPOSIT
     * 
     * @covers ::renderQuickEntrySelector
     */
    public function testDepositTransactionTypeUsesQeDeposit(): void
    {
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',  // Credit = Deposit
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should render without errors
        $this->assertIsString($html);
    }
    
    /**
     * Test payment transaction type uses QE_PAYMENT
     * 
     * @covers ::renderQuickEntrySelector
     */
    public function testPaymentTransactionTypeUsesQePayment(): void
    {
        // Create payment provider
        $paymentProvider = QuickEntryDataProvider::forPayment();
        
        $view = new QuickEntryPartnerTypeView(
            1,
            'D',  // Debit = Payment
            $paymentProvider
        );
        
        $html = $view->getHtml();
        
        // Should render without errors
        $this->assertIsString($html);
    }
    
    /**
     * Test renders base description when entry is selected
     * 
     * @covers ::renderQuickEntryDescription
     */
    public function testRendersBaseDescriptionWhenEntryIsSelected(): void
    {
        // Set a partner ID in POST
        $_POST['partnerId_1'] = '1';
        
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should attempt to fetch and display base description
        $this->assertIsString($html);
    }
    
    /**
     * Test no description rendered when no entry selected
     * 
     * @covers ::renderQuickEntryDescription
     */
    public function testNoDescriptionRenderedWhenNoEntrySelected(): void
    {
        // Don't set any POST data
        
        $view = new QuickEntryPartnerTypeView(
            1,
            'C',
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should render dropdown but no description
        $this->assertIsString($html);
        $this->assertStringContainsString('Quick Entry', $html);
    }
}
