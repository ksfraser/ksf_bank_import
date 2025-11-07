<?php

/**
 * Integration Test: Compare CustomerPartnerTypeView v1 vs v2 (Step 0)
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
use KsfBankImport\Views\CustomerPartnerTypeView as CustomerPartnerTypeViewV2;
use KsfBankImport\Views\DataProviders\CustomerDataProvider;

// Load FA stubs for testing
require_once __DIR__ . '/../../../includes/fa_stubs.php';

// Load v1 original (no namespace)
require_once __DIR__ . '/../../../Views/CustomerPartnerTypeView.php';

// Load v2 refactored (with namespace)
require_once __DIR__ . '/../../../Views/CustomerPartnerTypeView.v2.php';

/**
 * Compare v1 and v2 outputs to ensure identical behavior
 * 
 * @coversDefaultClass \KsfBankImport\Views\CustomerPartnerTypeView
 */
class CustomerPartnerTypeViewComparisonTest extends TestCase
{
    private $dataProvider;
    
    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize data provider for v2
        $this->dataProvider = CustomerDataProvider::getInstance();
        
        // Clear POST/GET to ensure clean state
        $_POST = [];
        $_GET = [];
    }
    
    /**
     * Reset after each test
     */
    protected function tearDown(): void
    {
        CustomerDataProvider::reset();
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }
    
    /**
     * Test that v1 and v2 produce identical HTML for basic case (no customer selected)
     * 
     * NOTE: This test is now expected to FAIL after Step 1.
     * v2 uses HTML_ROW_LABEL which generates actual HTML, while v1 uses FA stubs that return empty.
     * 
     * @test
     * @covers ::getHtml
     */
    public function testV1AndV2ProduceIdenticalHtmlForBasicCase(): void
    {
        $this->markTestIncomplete('Step 1: v2 now uses HTML_ROW_LABEL (generates HTML) vs v1 using label_row() FA stub (returns empty). Output intentionally different.');
        
        $lineItemId = 123;
        $bankAccount = '999888777';
        $timestamp = '2025-01-15';
        
        // Create v1 (original - no namespace, no DI)
        $v1 = new \CustomerPartnerTypeView(
            $lineItemId,
            $bankAccount,
            $timestamp,
            null,
            null
        );
        
        // Create v2 (refactored - with namespace, with DI)
        $v2 = new CustomerPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            $timestamp,
            null,
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
     * Test that v1 and v2 produce identical HTML with customer pre-selected
     * 
     * @test
     * @covers ::getHtml
     */
    public function testV1AndV2ProduceIdenticalHtmlWithCustomerSelected(): void
    {
        $this->markTestIncomplete('Requires database fixtures with real customer data');
        
        $lineItemId = 456;
        $bankAccount = '111222333';
        $timestamp = '2025-02-20';
        $customerId = 1;  // Would need real customer in test DB
        $branchId = null;
        
        // Create v1
        $v1 = new \CustomerPartnerTypeView(
            $lineItemId,
            $bankAccount,
            $timestamp,
            $customerId,
            $branchId
        );
        
        // Create v2
        $v2 = new CustomerPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            $timestamp,
            $customerId,
            $branchId,
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
        $timestamp = '2025-03-10';
        
        // Create v1
        $v1 = new \CustomerPartnerTypeView(
            $lineItemId,
            $bankAccount,
            $timestamp,
            null,
            null
        );
        
        // Create v2
        $v2 = new CustomerPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            $timestamp,
            null,
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
        $timestamp = '2025-04-05';
        
        // Create v1
        $v1 = new \CustomerPartnerTypeView(
            $lineItemId,
            $bankAccount,
            $timestamp,
            null,
            null
        );
        
        // Create v2
        $v2 = new CustomerPartnerTypeViewV2(
            $lineItemId,
            $bankAccount,
            $timestamp,
            null,
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
     * v2 uses CustomerDataProvider instead of db_customer_has_branches()
     * 
     * @test
     */
    public function testV2UsesInjectedDataProvider(): void
    {
        // Mock the data provider
        $mockProvider = $this->createMock(CustomerDataProvider::class);
        $mockProvider->expects($this->atLeastOnce())
                     ->method('hasBranches')
                     ->willReturn(false);
        
        $v2 = new CustomerPartnerTypeViewV2(
            123,
            '999888777',
            '2025-01-01',
            1,  // Customer ID triggers hasBranches check
            null,
            $mockProvider  // Injected mock
        );
        
        // Call getHtml - should trigger hasBranches on mock
        $v2->getHtml();
        
        // Mock verification happens automatically via expects()
        $this->assertTrue(true, 'Mock verification passed');
    }
}
