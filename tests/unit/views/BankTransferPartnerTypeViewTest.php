<?php
namespace Tests\Unit\Views;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\FaBankImport\Services\PartnerMatcher;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/Ksfraser/FaBankImport/Views/BankTransferPartnerTypeView.php';

/**
 * Test BankTransferPartnerTypeView
 * 
 * @package Tests\Unit\Views
 * @coversDefaultClass \Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView
 */
class BankTransferPartnerTypeViewTest extends TestCase
{
    /**
     * @var BankAccountDataProvider
     */
    private $dataProvider;
    
    /**
     * @var PartnerMatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $partnerMatcher;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dataProvider = new BankAccountDataProvider();
        $this->dataProvider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Test Account 1'],
            ['id' => '2', 'bank_account_name' => 'Test Account 2'],
        ]);
        
        $this->partnerMatcher = $this->createMock(PartnerMatcher::class);
    }
    
    protected function tearDown(): void
    {
        BankAccountDataProvider::resetCache();
        parent::tearDown();
    }
    
    /**
     * @covers ::__construct
     */
    public function testCanBeInstantiated(): void
    {
        $view = $this->createView();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView::class, $view);
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
    public function testGetHtmlContainsBankAccountOptions(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('Test Account 1', $html);
        $this->assertStringContainsString('Test Account 2', $html);
    }
    
    /**
     * @covers ::buildDirectionLabel
     */
    public function testCreditTransactionShowsFromDirection(): void
    {
        $view = $this->createView(['transactionDC' => 'C']);
        $html = $view->getHtml();
        $this->assertStringContainsString('Transfer to', $html);
        $this->assertStringContainsString('from', $html);
    }
    
    /**
     * @covers ::buildDirectionLabel
     */
    public function testDebitTransactionShowsToDirection(): void
    {
        $view = $this->createView(['transactionDC' => 'D']);
        $html = $view->getHtml();
        $this->assertStringContainsString('Transfer from', $html);
        $this->assertStringContainsString('To', $html);
    }
    
    /**
     * @covers ::autoMatchBankAccount
     */
    public function testAutoMatchesBankAccountWhenNoPartnerId(): void
    {
        $this->partnerMatcher->method('searchByBankAccount')
            ->with(ST_BANKTRANSFER, 'MATCH-ACCOUNT')
            ->willReturn(['partner_id' => 99, 'partner_detail_id' => 1]);
        $this->partnerMatcher->method('hasMatch')
            ->willReturn(true);
        $this->partnerMatcher->method('getPartnerId')
            ->willReturn(99);
        $this->partnerMatcher->method('getPartnerDetailId')
            ->willReturn(1);
        
        $view = $this->createView([
            'otherBankAccount' => 'MATCH-ACCOUNT',
            'partnerMatcher' => $this->partnerMatcher,
        ]);
        
        $view->getHtml();
        
        $this->assertSame(99, $view->getSelectedPartnerId());
        $this->assertSame(1, $view->getSelectedPartnerDetailId());
    }
    
    /**
     * @covers ::autoMatchBankAccount
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
     * @covers ::buildBankAccountSelect
     */
    public function testSelectsMatchingBankAccount(): void
    {
        $view = $this->createView(['partnerId' => 2]);
        $html = $view->getHtml();
        
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Test Account 2', $html);
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
     * @return \Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView
     */
    private function createView(array $params = []): \Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView
    {
        $lineItemId = $params['lineItemId'] ?? 1;
        $otherBankAccount = $params['otherBankAccount'] ?? 'TEST-ACCOUNT';
        $transactionDC = $params['transactionDC'] ?? 'C';
        $partnerId = $params['partnerId'] ?? null;
        $partnerDetailId = $params['partnerDetailId'] ?? null;
        $partnerMatcher = $params['partnerMatcher'] ?? $this->partnerMatcher;
        
        return new \Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView(
            $lineItemId,
            $otherBankAccount,
            $transactionDC,
            $this->dataProvider,
            $partnerId,
            $partnerDetailId,
            $partnerMatcher
        );
    }
}