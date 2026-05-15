<?php

/**
 * Unit Tests for SupplierPartnerTypeView (Final - Steps 0-2 complete)
 * 
 * @package    KsfBankImport\Tests\Unit\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use KsfBankImport\Views\SupplierPartnerTypeView;
use KsfBankImport\Views\DataProviders\SupplierDataProvider;

// Load FA stubs
require_once __DIR__ . '/../../../includes/fa_stubs.php';

// Load the view
require_once __DIR__ . '/../../../Views/SupplierPartnerTypeView.v2.php';

/**
 * Test Supplier Partner Type View
 * 
 * @coversDefaultClass \KsfBankImport\Views\SupplierPartnerTypeView
 */
class SupplierPartnerTypeViewFinalTest extends TestCase
{
    private $dataProvider;
    
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProvider = SupplierDataProvider::getInstance();
        $_POST = [];
        $_GET = [];
    }
    
    /**
     * Reset after each test
     */
    protected function tearDown(): void
    {
        SupplierDataProvider::reset();
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
        $view = new SupplierPartnerTypeView(
            1,
            'Test Bank Account',
            null,
            $this->dataProvider
        );
        
        $this->assertInstanceOf(SupplierPartnerTypeView::class, $view);
    }
    
    /**
     * Test getHtml returns string
     * 
     * @covers ::getHtml
     */
    public function testGetHtmlReturnsString(): void
    {
        $view = new SupplierPartnerTypeView(
            1,
            'Test Bank Account',
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * Test HTML contains payment to label row
     * 
     * @covers ::getHtml
     */
    public function testHtmlContainsPaymentToLabel(): void
    {
        $view = new SupplierPartnerTypeView(
            1,
            'Test Bank Account',
            null,
            $this->dataProvider
        );
        
        $html = $view->getHtml();
        
        // Should contain the label text
        $this->assertStringContainsString('Payment To:', $html);
        
        // Should contain table row structure
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('<td', $html);
    }
    
    /**
     * Test uses data provider for supplier checking
     * 
     * @covers ::getHtml
     */
    public function testUsesDataProviderForSupplierChecking(): void
    {
        // This test validates the DI is working
        // We can't easily test the supplier list without DB fixtures,
        // but we can verify the view doesn't crash
        
        $view = new SupplierPartnerTypeView(
            1,
            'Test Bank Account',
            1,  // Supplier ID
            $this->dataProvider
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
        $view = new SupplierPartnerTypeView(
            1,
            'Test Bank Account',
            null,
            $this->dataProvider
        );
        
        ob_start();
        $view->display();
        $output = ob_get_clean();
        
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Payment To:', $output);
    }
    
    /**
     * Test HTML structure is well formed
     * 
     * Validates all tags are properly closed
     * 
     * @covers ::getHtml
     */
    public function testHtmlStructureIsWellFormed(): void
    {
        $view = new SupplierPartnerTypeView(
            1,
            'Test Bank Account',
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
