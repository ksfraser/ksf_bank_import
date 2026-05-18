<?php
namespace Tests\Unit\Views;

use Ksfraser\FaBankImport\Views\DataProviders\SupplierDataProvider;
use Ksfraser\FaBankImport\Services\PartnerMatcher;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/Ksfraser/FaBankImport/Views/SupplierPartnerTypeView.php';

/**
 * Test SupplierPartnerTypeView
 * 
 * @package Tests\Unit\Views
 * @coversDefaultClass \Ksfraser\FaBankImport\Views\SupplierPartnerTypeView
 */
class SupplierPartnerTypeViewTest extends TestCase
{
    /**
     * @var SupplierDataProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataProvider;
    
    /**
     * @var PartnerMatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $partnerMatcher;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dataProvider = $this->createMock(SupplierDataProvider::class);
        $this->dataProvider->method('getPartners')->willReturn([
            1 => ['supplier_id' => 1, 'supp_name' => 'Supplier A'],
            2 => ['supplier_id' => 2, 'supp_name' => 'Supplier B'],
        ]);
        
        $this->partnerMatcher = $this->createMock(PartnerMatcher::class);
    }
    
    /**
     * @covers ::__construct
     */
    public function testCanBeInstantiated(): void
    {
        $view = $this->createView();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\SupplierPartnerTypeView::class, $view);
    }
    
    /**
     * @covers ::getLineItemId
     */
    public function testGetLineItemIdReturnsCorrectValue(): void
    {
        $view = $this->createView(['lineItemId' => 42]);
        $this->assertSame(42, $view->getLineItemId());
    }
    
    /**
     * @covers ::getSelectedPartnerId
     */
    public function testGetSelectedPartnerIdReturnsNullWhenNotSet(): void
    {
        $view = $this->createView();
        $this->assertNull($view->getSelectedPartnerId());
    }
    
    /**
     * @covers ::getSelectedPartnerId
     */
    public function testGetSelectedPartnerIdReturnsSetPartnerId(): void
    {
        $view = $this->createView(['partnerId' => 123]);
        $this->assertSame(123, $view->getSelectedPartnerId());
    }
    
    /**
     * @covers ::getSelectedPartnerDetailId
     */
    public function testGetSelectedPartnerDetailIdReturnsNull(): void
    {
        $view = $this->createView();
        $this->assertNull($view->getSelectedPartnerDetailId());
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlReturnsString(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsSelectElement(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('partnerId_1', $html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsSupplierOptions(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('Supplier A', $html);
        $this->assertStringContainsString('Supplier B', $html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsPaymentToLabel(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('Payment To:', $html);
    }
    
    /**
     * @covers ::autoMatchSupplier
     */
    public function testAutoMatchesSupplierWhenNoPartnerId(): void
    {
        $this->partnerMatcher->method('searchByBankAccount')
            ->with(PT_SUPPLIER, 'MATCH-ACCOUNT')
            ->willReturn(['partner_id' => 99]);
        $this->partnerMatcher->method('hasMatch')
            ->willReturn(true);
        $this->partnerMatcher->method('getPartnerId')
            ->willReturn(99);
        
        $view = $this->createView([
            'otherBankAccount' => 'MATCH-ACCOUNT',
            'partnerMatcher' => $this->partnerMatcher,
        ]);
        
        $view->getHtml();
        
        $this->assertSame(99, $view->getSelectedPartnerId());
    }
    
    /**
     * @covers ::autoMatchSupplier
     */
    public function testDoesNotOverrideExistingPartnerId(): void
    {
        $this->partnerMatcher->method('searchByBankAccount')
            ->willReturn([]);
        $this->partnerMatcher->method('hasMatch')
            ->willReturn(false);
        
        $view = $this->createView([
            'partnerId' => 50,
            'partnerMatcher' => $this->partnerMatcher,
        ]);
        
        $view->getHtml();
        
        $this->assertSame(50, $view->getSelectedPartnerId());
    }
    
    /**
     * @covers ::buildSupplierSelect
     */
    public function testSelectsMatchingSupplier(): void
    {
        $view = $this->createView(['partnerId' => 2]);
        $html = $view->getHtml();
        
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Supplier B', $html);
    }
    
    /**
     * @covers ::toHtml
     */
    public function testToHtmlOutputsDirectly(): void
    {
        $view = $this->createView();
        
        ob_start();
        $view->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<select', $output);
        $this->assertNotEmpty($output);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testSelectHasOnChangeAttribute(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('onchange', $html);
    }
    
    /**
     * Helper method to create view with customizable parameters
     * 
     * @param array $params Override parameters
     * @return \Ksfraser\FaBankImport\Views\SupplierPartnerTypeView
     */
    private function createView(array $params = []): \Ksfraser\FaBankImport\Views\SupplierPartnerTypeView
    {
        $lineItemId = $params['lineItemId'] ?? 1;
        $otherBankAccount = $params['otherBankAccount'] ?? 'TEST-ACCOUNT';
        $partnerId = $params['partnerId'] ?? null;
        $partnerMatcher = $params['partnerMatcher'] ?? $this->partnerMatcher;
        
        return new \Ksfraser\FaBankImport\Views\SupplierPartnerTypeView(
            $lineItemId,
            $otherBankAccount,
            $this->dataProvider,
            $partnerId,
            $partnerMatcher
        );
    }
}