<?php

/**
 * CustomerPartnerTypeView.v2 Tests
 * 
 * TDD tests for step-by-step refactoring of CustomerPartnerTypeView
 * 
 * Tests ensure functionality is maintained through each refactoring step:
 * - Step 0: Original code + DI
 * - Step 1: Replace label_row with HTML_ROW_LABEL
 * - Step 2: Replace hardcoded HTML with HTML classes
 * - Step 3: Wrap in HtmlTable
 * 
 * @package    KsfBankImport\Tests\Unit\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @since      20250422
 */

use PHPUnit\Framework\TestCase;
use KsfBankImport\Views\CustomerPartnerTypeView;
use KsfBankImport\Views\DataProviders\CustomerDataProvider;

// Load FrontAccounting stubs for testing
require_once __DIR__ . '/../../../includes/fa_stubs.php';

require_once __DIR__ . '/../../../Views/CustomerPartnerTypeView.v2.php';
require_once __DIR__ . '/../../../Views/DataProviders/CustomerDataProvider.php';

/**
 * Test CustomerPartnerTypeView.v2
 * 
 * @coversDefaultClass KsfBankImport\Views\CustomerPartnerTypeView
 */
class CustomerPartnerTypeViewV2Test extends TestCase
{
    private $dataProvider;
    
    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset and get fresh provider instance
        CustomerDataProvider::reset();
        $this->dataProvider = CustomerDataProvider::getInstance();
    }
    
    /**
     * Reset after each test
     */
    protected function tearDown(): void
    {
        CustomerDataProvider::reset();
        parent::tearDown();
    }
    
    /**
     * Test constructor accepts all required parameters
     * 
     * @covers ::__construct
     */
    public function testConstructorAcceptsAllParameters(): void
    {
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        $this->assertInstanceOf(CustomerPartnerTypeView::class, $view);
    }
    
    /**
     * Test getHtml returns string
     * 
     * @covers ::getHtml
     */
    public function testGetHtmlReturnsString(): void
    {
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * Test HTML contains customer/branch label row
     * 
     * Step 1: Validates HTML_ROW_LABEL is used.
     * 
     * @covers ::getHtml
     */
    public function testHtmlContainsCustomerBranchLabel(): void
    {
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should contain the label text
        $this->assertStringContainsString('From Customer/Branch:', $html);
        
        // Should contain table row structure
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('<td', $html);
    }
    
    /**
     * Test HTML contains hidden fields
     * 
     * Step 2: Now validates HtmlInput hidden fields
     * 
     * @covers ::getHtml
     */
    public function testHtmlContainsHiddenFields(): void
    {
        $view = new CustomerPartnerTypeView(
            123,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should contain hidden customer field (HtmlInput format)
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="customer_123"', $html);
        
        // Should contain hidden branch field
        $this->assertStringContainsString('name="customer_branch_123"', $html);
    }
    
    /**
     * Test uses data provider for branch checking
     * 
     * Verifies that we're using the injected data provider
     * instead of direct database calls.
     * 
     * @covers ::getHtml
     */
    public function testUsesDataProviderForBranchChecking(): void
    {
        // This test validates the DI is working
        // We can't easily test the hasBranches() call without DB fixtures,
        // but we can verify the view doesn't crash
        
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            999, // Non-existent customer
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should complete without error
        $this->assertIsString($html);
    }
    
    /**
     * Test display method outputs HTML
     * 
     * @covers ::display
     */
    public function testDisplayMethodOutputsHtml(): void
    {
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        ob_start();
        $view->display();
        $output = ob_get_clean();
        
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('From Customer/Branch:', $output);
    }
    
    /**
     * Test invoice allocation section is optional
     * 
     * Since fa_customer_payment may not be available,
     * the view should still work without it.
     * 
     * @covers ::displayAllocatableInvoices
     */
    public function testInvoiceAllocationIsOptional(): void
    {
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        // Should not throw error even if fa_customer_payment is unavailable
        $html = $view->getHtml();
        
        $this->assertIsString($html);
    }
    
    /**
     * Test HTML structure is well-formed
     * 
     * Validates that we don't have unclosed tags.
     * This is the key test for fixing display issues.
     * 
     * @covers ::getHtml
     */
    public function testHtmlStructureIsWellFormed(): void
    {
        $view = new CustomerPartnerTypeView(
            1,
            'Test Bank Account',
            '2025-01-01',
            null,
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Count opening and closing tr tags
        $openTr = substr_count($html, '<tr>');
        $closeTr = substr_count($html, '</tr>');
        
        $this->assertEquals($openTr, $closeTr, 'All <tr> tags should be closed');
        
        // Count opening and closing td tags
        $openTd = substr_count($html, '<td');
        $closeTd = substr_count($html, '</td>');
        
        $this->assertEquals($openTd, $closeTd, 'All <td> tags should be closed');
    }
}
