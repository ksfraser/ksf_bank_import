<?php
namespace Tests\Unit\Views;

use Ksfraser\FaBankImport\Views\DataProviders\CustomerDataProvider;
use Ksfraser\FaBankImport\Services\PartnerMatcher;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/Ksfraser/FaBankImport/Views/CustomerPartnerTypeView.php';

/**
 * Test CustomerPartnerTypeView
 * 
 * @package Tests\Unit\Views
 * @coversDefaultClass \Ksfraser\FaBankImport\Views\CustomerPartnerTypeView
 */
class CustomerPartnerTypeViewTest extends TestCase
{
    /**
     * @var CustomerDataProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataProvider;
    
    /**
     * @var PartnerMatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $partnerMatcher;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dataProvider = $this->createMock(CustomerDataProvider::class);
        $this->dataProvider->method('getCustomers')->willReturn([
            1 => ['debtor_no' => 1, 'name' => 'Customer A'],
            2 => ['debtor_no' => 2, 'name' => 'Customer B'],
        ]);
        $this->dataProvider->method('hasBranches')->willReturn(false);
        
        $this->partnerMatcher = $this->createMock(PartnerMatcher::class);
    }
    
    /**
     * @covers ::__construct
     */
    public function testCanBeInstantiated(): void
    {
        $view = $this->createView();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\CustomerPartnerTypeView::class, $view);
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
    public function testGetSelectedPartnerDetailIdReturnsNullWhenNotSet(): void
    {
        $view = $this->createView();
        $this->assertNull($view->getSelectedPartnerDetailId());
    }
    
    /**
     * @covers ::getSelectedPartnerDetailId
     */
    public function testGetSelectedPartnerDetailIdReturnsSetPartnerDetailId(): void
    {
        $view = $this->createView(['partnerDetailId' => 5]);
        $this->assertSame(5, $view->getSelectedPartnerDetailId());
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
    public function testGetHtmlContainsCustomerSelect(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('partnerId_1', $html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsCustomerOptions(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('Customer A', $html);
        $this->assertStringContainsString('Customer B', $html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsCustomerBranchLabel(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('From Customer/Branch:', $html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsHiddenFields(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('customer_1', $html);
        $this->assertStringContainsString('customer_branch_1', $html);
    }
    
    /**
     * @covers ::autoMatchCustomer
     */
    public function testAutoMatchesCustomerWhenNoPartnerId(): void
    {
        $this->partnerMatcher->method('searchByBankAccount')
            ->with(PT_CUSTOMER, 'MATCH-ACCOUNT')
            ->willReturn(['partner_id' => 99, 'partner_detail_id' => 2]);
        $this->partnerMatcher->method('hasMatch')
            ->willReturn(true);
        $this->partnerMatcher->method('getPartnerId')
            ->willReturn(99);
        $this->partnerMatcher->method('getPartnerDetailId')
            ->willReturn(2);
        
        $view = $this->createView([
            'otherBankAccount' => 'MATCH-ACCOUNT',
            'partnerMatcher' => $this->partnerMatcher,
        ]);
        
        $view->getHtml();
        
        $this->assertSame(99, $view->getSelectedPartnerId());
        $this->assertSame(2, $view->getSelectedPartnerDetailId());
    }
    
    /**
     * @covers ::autoMatchCustomer
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
     * @covers ::buildCustomerSelect
     */
    public function testSelectsMatchingCustomer(): void
    {
        $view = $this->createView(['partnerId' => 2]);
        $html = $view->getHtml();
        
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Customer B', $html);
    }
    
    /**
     * @covers ::buildBranchContent
     */
    public function testShowsBranchHiddenFieldWhenNoBranches(): void
    {
        $this->dataProvider->method('hasBranches')->willReturn(false);
        
        $view = $this->createView(['partnerId' => 1]);
        $html = $view->getHtml();
        
        $this->assertStringContainsString('partnerDetailId_1', $html);
    }
    
    /**
     * @covers ::buildBranchContent
     */
    public function testShowsBranchSelectWhenCustomerHasBranches(): void
    {
        $dataProvider = $this->createMock(CustomerDataProvider::class);
        $dataProvider->method('getCustomers')->willReturn([
            1 => ['debtor_no' => 1, 'name' => 'Customer A'],
        ]);
        $dataProvider->method('hasBranches')->willReturn(true);
        $dataProvider->method('getBranches')->willReturn([
            1 => ['branch_code' => 1, 'br_name' => 'Branch A'],
            2 => ['branch_code' => 2, 'br_name' => 'Branch B'],
        ]);
        
        $view = new \Ksfraser\FaBankImport\Views\CustomerPartnerTypeView(
            1,
            'TEST-ACCOUNT',
            '2025-10-19',
            $dataProvider,
            1,
            2,
            $this->partnerMatcher
        );
        $html = $view->getHtml();
        
        $this->assertStringContainsString('Branch A', $html);
        $this->assertStringContainsString('Branch B', $html);
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
     * Helper method to create view with customizable parameters
     * 
     * @param array $params Override parameters
     * @return \Ksfraser\FaBankImport\Views\CustomerPartnerTypeView
     */
    private function createView(array $params = []): \Ksfraser\FaBankImport\Views\CustomerPartnerTypeView
    {
        $lineItemId = $params['lineItemId'] ?? 1;
        $otherBankAccount = $params['otherBankAccount'] ?? 'TEST-ACCOUNT';
        $valueTimestamp = $params['valueTimestamp'] ?? '2025-10-19';
        $partnerId = $params['partnerId'] ?? null;
        $partnerDetailId = $params['partnerDetailId'] ?? null;
        $partnerMatcher = $params['partnerMatcher'] ?? $this->partnerMatcher;
        
        return new \Ksfraser\FaBankImport\Views\CustomerPartnerTypeView(
            $lineItemId,
            $otherBankAccount,
            $valueTimestamp,
            $this->dataProvider,
            $partnerId,
            $partnerDetailId,
            $partnerMatcher
        );
    }
}