<?php

/**
 * Unit Tests for ViewFactory
 * 
 * @package    Tests\Unit\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @version    1.0.0
 */
/** @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\ViewFactory;
use Ksfraser\FaBankImport\Views\SupplierPartnerTypeView;
use Ksfraser\FaBankImport\Views\CustomerPartnerTypeView;
use Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView;
use Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView;

// Load FA stubs

// Load the factory
require_once __DIR__ . '/../../../Views/ViewFactory.php';

/**
 * Test ViewFactory
 * 
 * @coversDefaultClass \Ksfraser\FaBankImport\Views\ViewFactory
 */
class ViewFactoryTest extends TestCase
{
    /**
     * Reset after each test
     */
    protected function tearDown(): void
    {
        // Reset all data provider singletons
        \Ksfraser\FaBankImport\Views\DataProviders\SupplierDataProvider::reset();
        \Ksfraser\FaBankImport\Views\DataProviders\CustomerDataProvider::reset();
        \Ksfraser\FaBankImport\Views\DataProviders\QuickEntryDataProvider::reset();
        
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }
    
    /**
     * Test creates supplier view
     * 
     * @covers ::createPartnerTypeView
     * @covers ::createSupplierView
     */
    public function testCreatesSupplierView(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'supplier',
            1,
            [
                'otherBankAccount' => 'Test Account',
                'partnerId' => 123
            ]
        );
        
        $this->assertInstanceOf(SupplierPartnerTypeView::class, $view);
    }
    
    /**
     * Test creates customer view
     * 
     * @covers ::createPartnerTypeView
     * @covers ::createCustomerView
     */
    public function testCreatesCustomerView(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'customer',
            1,
            [
                'otherBankAccount' => 'Test Account',
                'valueTimestamp' => '2025-10-24',
                'partnerId' => 123,
                'partnerDetailId' => 456
            ]
        );
        
        $this->assertInstanceOf(CustomerPartnerTypeView::class, $view);
    }
    
    /**
     * Test creates bank transfer view
     * 
     * @covers ::createPartnerTypeView
     * @covers ::createBankTransferView
     */
    public function testCreatesBankTransferView(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'bank_transfer',
            1,
            [
                'otherBankAccount' => 'Test Account',
                'transactionDC' => 'C',
                'partnerId' => 123,
                'partnerDetailId' => 456
            ]
        );
        
        $this->assertInstanceOf(BankTransferPartnerTypeView::class, $view);
    }
    
    /**
     * Test creates quick entry view for deposit
     * 
     * @covers ::createPartnerTypeView
     * @covers ::createQuickEntryView
     */
    public function testCreatesQuickEntryViewForDeposit(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'quick_entry',
            1,
            ['transactionDC' => 'C']
        );
        
        $this->assertInstanceOf(QuickEntryPartnerTypeView::class, $view);
    }
    
    /**
     * Test creates quick entry view for payment
     * 
     * @covers ::createPartnerTypeView
     * @covers ::createQuickEntryView
     */
    public function testCreatesQuickEntryViewForPayment(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'quick_entry',
            1,
            ['transactionDC' => 'D']
        );
        
        $this->assertInstanceOf(QuickEntryPartnerTypeView::class, $view);
    }
    
    /**
     * Test throws exception for unknown partner type
     * 
     * @covers ::createPartnerTypeView
     */
    public function testThrowsExceptionForUnknownPartnerType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown partner type: invalid_type');
        
        ViewFactory::createPartnerTypeView(
            'invalid_type',
            1,
            []
        );
    }
    
    /**
     * Test uses constants for partner types
     * 
     * @covers ::createPartnerTypeView
     */
    public function testUsesConstantsForPartnerTypes(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            ViewFactory::PARTNER_TYPE_SUPPLIER,
            1,
            ['otherBankAccount' => 'Test']
        );
        
        $this->assertInstanceOf(SupplierPartnerTypeView::class, $view);
    }
    
    /**
     * Test get valid partner types returns array
     * 
     * @covers ::getValidPartnerTypes
     */
    public function testGetValidPartnerTypesReturnsArray(): void
    {
        $types = ViewFactory::getValidPartnerTypes();
        
        $this->assertIsArray($types);
        $this->assertCount(4, $types);
        $this->assertContains('supplier', $types);
        $this->assertContains('customer', $types);
        $this->assertContains('bank_transfer', $types);
        $this->assertContains('quick_entry', $types);
    }
    
    /**
     * Test supplier view with minimal context
     * 
     * @covers ::createSupplierView
     */
    public function testSupplierViewWithMinimalContext(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'supplier',
            1,
            [] // Empty context - should use defaults
        );
        
        $this->assertInstanceOf(SupplierPartnerTypeView::class, $view);
    }
    
    /**
     * Test customer view with minimal context
     * 
     * @covers ::createCustomerView
     */
    public function testCustomerViewWithMinimalContext(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'customer',
            1,
            [] // Empty context - should use defaults
        );
        
        $this->assertInstanceOf(CustomerPartnerTypeView::class, $view);
    }
    
    /**
     * Test bank transfer view with minimal context
     * 
     * @covers ::createBankTransferView
     */
    public function testBankTransferViewWithMinimalContext(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'bank_transfer',
            1,
            [] // Empty context - should use defaults
        );
        
        $this->assertInstanceOf(BankTransferPartnerTypeView::class, $view);
    }
    
    /**
     * Test created views can generate HTML
     * 
     * @covers ::createPartnerTypeView
     */
    public function testCreatedViewsCanGenerateHtml(): void
    {
        $view = ViewFactory::createPartnerTypeView(
            'supplier',
            1,
            ['otherBankAccount' => 'Test Account']
        );
        
        $html = $view->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
}
