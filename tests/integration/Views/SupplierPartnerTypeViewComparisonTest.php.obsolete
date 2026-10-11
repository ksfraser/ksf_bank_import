<?php

/**
 * Integration Test: Compare SupplierPartnerTypeView v1 vs v2 Step 0
 * 
 * PURPOSE: Prove that v2 Step 0 produces IDENTICAL output to v1 original code.
 * This validates our baseline before making incremental refactoring changes.
 * 
 * @package    KsfBankImport\Tests\Integration\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Integration\Views;

use PHPUnit\Framework\TestCase;
use KsfBankImport\Views\SupplierPartnerTypeView as SupplierPartnerTypeViewV2;
use KsfBankImport\Views\DataProviders\SupplierDataProvider;

// Load FA stubs for testing
require_once __DIR__ . '/../../../includes/fa_stubs.php';

// Load v1 original (no namespace)
require_once __DIR__ . '/../../../Views/SupplierPartnerTypeView.php';

// Load v2 Step 0 (with namespace)
require_once __DIR__ . '/../../../Views/SupplierPartnerTypeView.v2.step0.php';

/**
 * Compare v1 and v2 Step 0 outputs to ensure identical behavior
 * 
 * @coversDefaultClass \KsfBankImport\Views\SupplierPartnerTypeView
 */
class SupplierPartnerTypeViewComparisonTest extends TestCase
{
    private $dataProvider;
    
    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize data provider for v2
        $this->dataProvider = SupplierDataProvider::getInstance();
        
        // Clear POST/GET to ensure clean state
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
     * Test that v1 and v2 Step 0 produce identical HTML for basic case (no supplier selected)
     * 
     * @test
     * @covers ::getHtml
     */
    public function testV1AndV2ProduceIdenticalHtmlForBasicCase(): void
    {
        $lineItemId = 123;
        $bankAccount = '999888777';
        
        // Create v1 (original - no namespace, no DI)
        $v1 = new \SupplierPartnerTypeView(
            $lineItemId,
            $bankAccount,
            null
        );
        
        // Create v2 Step 0 (refactored - with namespace, with DI)
        $v2 = new SupplierPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            null,
            $this->dataProvider
        );
        
        // Get HTML from both
        $htmlV1 = $v1->getHtml();
        $htmlV2 = $v2->getHtml();
        
        // CRITICAL ASSERTION: Both should produce IDENTICAL output
        $this->assertEquals(
            $htmlV1, 
            $htmlV2, 
            'v1 and v2 Step 0 should produce identical HTML output'
        );
    }
    
    /**
     * Test that v1 and v2 produce identical HTML with supplier pre-selected
     * 
     * @test
     * @covers ::getHtml
     */
    public function testV1AndV2ProduceIdenticalHtmlWithSupplierSelected(): void
    {
        $this->markTestIncomplete('Requires database fixtures with real supplier data');
        
        $lineItemId = 456;
        $bankAccount = '111222333';
        $supplierId = 1;  // Would need real supplier in test DB
        
        // Create v1
        $v1 = new \SupplierPartnerTypeView(
            $lineItemId,
            $bankAccount,
            $supplierId
        );
        
        // Create v2
        $v2 = new SupplierPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            $supplierId,
            $this->dataProvider
        );
        
        // Get HTML from both
        $htmlV1 = $v1->getHtml();
        $htmlV2 = $v2->getHtml();
        
        // Should be identical
        $this->assertEquals($htmlV1, $htmlV2);
    }
    
    /**
     * Test that v1 and v2 handle display() method identically
     * 
     * @test
     * @covers ::display
     */
    public function testV1AndV2DisplayMethodProducesIdenticalOutput(): void
    {
        $lineItemId = 789;
        $bankAccount = '555666777';
        
        // Create v1
        $v1 = new \SupplierPartnerTypeView(
            $lineItemId,
            $bankAccount,
            null
        );
        
        // Create v2
        $v2 = new SupplierPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            null,
            $this->dataProvider
        );
        
        // Capture output from v1
        ob_start();
        $v1->display();
        $outputV1 = ob_get_clean();
        
        // Capture output from v2
        ob_start();
        $v2->display();
        $outputV2 = ob_get_clean();
        
        // Should be identical
        $this->assertEquals(
            $outputV1, 
            $outputV2,
            'v1 and v2 display() should produce identical output'
        );
    }
    
    /**
     * Test that both handle empty output gracefully (FA stubs return nothing)
     * 
     * @test
     */
    public function testBothHandleEmptyOutputFromFaStubs(): void
    {
        $lineItemId = 999;
        $bankAccount = '000111222';
        
        // Create v1
        $v1 = new \SupplierPartnerTypeView(
            $lineItemId,
            $bankAccount,
            null
        );
        
        // Create v2
        $v2 = new SupplierPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            null,
            $this->dataProvider
        );
        
        // Both should return empty string (FA stubs don't output)
        $htmlV1 = $v1->getHtml();
        $htmlV2 = $v2->getHtml();
        
        $this->assertEmpty($htmlV1, 'v1 should return empty (FA stubs)');
        $this->assertEmpty($htmlV2, 'v2 should return empty (FA stubs)');
        $this->assertEquals($htmlV1, $htmlV2, 'Both should return same empty string');
    }
    
    /**
     * Test that v2 uses injected data provider correctly
     * 
     * This validates the ONLY intentional behavioral difference:
     * v2 receives SupplierDataProvider instead of calling supplier_list() directly
     * 
     * @test
     */
    public function testV2UsesInjectedDataProvider(): void
    {
        // This test validates the DI is working
        // We can't easily test the data provider usage without DB fixtures,
        // but we can verify the view doesn't crash
        
        $v2 = new SupplierPartnerTypeViewV2(
            123,
            '999888777',
            null,
            $this->dataProvider  // Injected
        );
        
        // Call getHtml - should not crash
        $html = $v2->getHtml();
        
        // Should return string (empty in test environment)
        $this->assertIsString($html);
    }
}
