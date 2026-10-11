<?php
namespace Tests\Unit\Views;

use Ksfraser\FaBankImport\Views\DataProviders\QuickEntryDataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/Ksfraser/FaBankImport/Views/QuickEntryPartnerTypeView.php';

/**
 * Test QuickEntryPartnerTypeView
 * 
 * @package Tests\Unit\Views
 * @coversDefaultClass \Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView
 */
class QuickEntryPartnerTypeViewTest extends TestCase
{
    /**
     * @var QuickEntryDataProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataProvider;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dataProvider = $this->createMock(QuickEntryDataProvider::class);
        $this->dataProvider->method('getEntries')->willReturn([
            1 => ['id' => 1, 'description' => 'Deposit Entry 1', 'base_desc' => 'Base Deposit'],
            2 => ['id' => 2, 'description' => 'Deposit Entry 2', 'base_desc' => 'Base Payment'],
        ]);
        $this->dataProvider->method('getEntry')->willReturnMap([
            [1, ['id' => 1, 'description' => 'Deposit Entry 1', 'base_desc' => 'Base Deposit']],
            [2, ['id' => 2, 'description' => 'Deposit Entry 2', 'base_desc' => 'Base Payment']],
        ]);
    }
    
    /**
     * @covers ::__construct
     */
    public function testCanBeInstantiated(): void
    {
        $view = $this->createView();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView::class, $view);
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
    public function testGetHtmlContainsQuickEntryOptions(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('Deposit Entry 1', $html);
        $this->assertStringContainsString('Deposit Entry 2', $html);
    }
    
    /**
     * @covers ::getHtml
     */
    public function testGetHtmlContainsQuickEntryLabel(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        $this->assertStringContainsString('Quick Entry:', $html);
    }
    
    /**
     * @covers ::getQuickEntryDescription
     */
    public function testShowsBaseDescriptionWhenEntrySelected(): void
    {
        $_POST['partnerId_1'] = 1;
        
        $view = $this->createView();
        $html = $view->getHtml();
        
        $this->assertStringContainsString('Base Deposit', $html);
        
        unset($_POST['partnerId_1']);
    }
    
    /**
     * @covers ::getQuickEntryDescription
     */
    public function testNoDescriptionWhenNoEntrySelected(): void
    {
        $view = $this->createView();
        $html = $view->getHtml();
        
        $this->assertStringNotContainsString('Base Deposit', $html);
    }
    
    /**
     * @covers ::buildQuickEntrySelect
     */
    public function testSelectsMatchingEntry(): void
    {
        $_POST['partnerId_1'] = 2;
        
        $view = $this->createView();
        $html = $view->getHtml();
        
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Deposit Entry 2', $html);
        
        unset($_POST['partnerId_1']);
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
     * @covers ::__construct
     */
    public function testWorksWithDepositTransactionType(): void
    {
        $view = $this->createView(['transactionDC' => 'C']);
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView::class, $view);
    }
    
    /**
     * @covers ::__construct
     */
    public function testWorksWithPaymentTransactionType(): void
    {
        $view = $this->createView(['transactionDC' => 'D']);
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView::class, $view);
    }
    
    /**
     * Helper method to create view with customizable parameters
     * 
     * @param array $params Override parameters
     * @return \Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView
     */
    private function createView(array $params = []): \Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView
    {
        $lineItemId = $params['lineItemId'] ?? 1;
        $transactionDC = $params['transactionDC'] ?? 'C';
        
        return new \Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView(
            $lineItemId,
            $transactionDC,
            $this->dataProvider
        );
    }
}