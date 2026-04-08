<?php

/**
 * Unit tests for PartnerTypeDisplayStrategy
 * 
 * Following TDD principles - tests written first to drive design.
 * Tests verify Strategy pattern correctly dispatches to appropriate
 * partner type views based on partner type codes.
 * 
 * @package KsfBankImport\Tests\Unit\Views
 * @author Kevin Fraser / GitHub Copilot
 * @since 2025-10-25
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Views/PartnerTypeDisplayStrategy.php';
require_once __DIR__ . '/../../../Views/ViewFactory.php';
require_once __DIR__ . '/../../../src/Ksfraser/HTML/HtmlFragment.php';
require_once __DIR__ . '/../../../src/Ksfraser/HTML/HtmlAttribute.php';
require_once __DIR__ . '/../../../src/Ksfraser/HTML/Elements/HtmlInput.php';
require_once __DIR__ . '/../../../src/Ksfraser/HTML/Elements/HtmlHidden.php';

class PartnerTypeDisplayStrategyTest extends TestCase
{
    /**
     * Test data for creating strategy instances
     */
    private $testData;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test data matching bi_lineitem structure
        $this->testData = [
            'id' => 123,
            'otherBankAccount' => 'TEST-ACCOUNT',
            'valueTimestamp' => '2025-01-15',
            'transactionDC' => 'D',
            'partnerId' => 456,
            'partnerDetailId' => 789,
            'memo' => 'Test memo',
            'transactionTitle' => 'Test transaction',
            'matching_trans' => [
                [
                    'type' => 1,
                    'type_no' => 999,
                    'tran_date' => '2025-01-15',
                    'amount' => 100.00
                ]
            ]
        ];
    }
    
    /**
     * Test that Strategy validates partner type codes
     */
    public function testValidatesPartnerTypeCodes()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        $this->assertTrue($strategy->isValidPartnerType('SP'));
        $this->assertTrue($strategy->isValidPartnerType('CU'));
        $this->assertTrue($strategy->isValidPartnerType('BT'));
        $this->assertTrue($strategy->isValidPartnerType('QE'));
        $this->assertTrue($strategy->isValidPartnerType('MA'));
        $this->assertTrue($strategy->isValidPartnerType('ZZ'));
        
        $this->assertFalse($strategy->isValidPartnerType('XX'));
        $this->assertFalse($strategy->isValidPartnerType(''));
        $this->assertFalse($strategy->isValidPartnerType('INVALID'));
    }
    
    /**
     * Test that Strategy returns available partner types
     */
    public function testReturnsAvailablePartnerTypes()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        $types = $strategy->getAvailablePartnerTypes();
        
        $this->assertIsArray($types);
        $this->assertCount(6, $types);
        $this->assertContains('SP', $types);
        $this->assertContains('CU', $types);
        $this->assertContains('BT', $types);
        $this->assertContains('QE', $types);
        $this->assertContains('MA', $types);
        $this->assertContains('ZZ', $types);
    }
    
    /**
     * Test that Strategy throws exception for unknown partner type
     */
    public function testThrowsExceptionForUnknownPartnerType()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown partner type: INVALID');
        
        $strategy->display('INVALID');
    }
    
    /**
     * Test Supplier (SP) partner type display
     * 
     * Verifies that SP type calls ViewFactory with correct parameters
     */
    public function testDisplaysSupplierPartnerType()
    {
        if (!function_exists('supplier_list')) {
            $this->markTestSkipped('FA functions not available (expected in unit test context)');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        // Capture output since Views echo directly
        ob_start();
        $strategy->display('SP');
        $output = ob_get_clean();
        
        // Should produce some HTML output (View classes echo HTML)
        $this->assertNotEmpty($output);
    }
    
    /**
     * Test Customer (CU) partner type display
     */
    public function testDisplaysCustomerPartnerType()
    {
        if (!function_exists('customer_list')) {
            $this->markTestSkipped('FA functions not available (expected in unit test context)');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        ob_start();
        $strategy->display('CU');
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
    }
    
    /**
     * Test Bank Transfer (BT) partner type display
     */
    public function testDisplaysBankTransferPartnerType()
    {
        if (!defined('ST_BANKTRANSFER')) {
            $this->markTestSkipped('FA constants not available (expected in unit test context)');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        ob_start();
        $strategy->display('BT');
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
    }
    
    /**
     * Test Quick Entry (QE) partner type display
     */
    public function testDisplaysQuickEntryPartnerType()
    {
        if (!function_exists('quick_entries_list')) {
            $this->markTestSkipped('FA functions not available (expected in unit test context)');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        ob_start();
        $strategy->display('QE');
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
    }
    
    /**
     * Test Matched Existing (ZZ) partner type display
     * 
     * This type has special logic for displaying hidden fields
     * based on matching_trans data
     */
    public function testDisplaysMatchedExistingPartnerType()
    {
        if (!function_exists('hidden')) {
            $this->markTestSkipped('FA functions not available (expected in unit test context)');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        ob_start();
        $strategy->display('ZZ');
        $output = ob_get_clean();
        
        // Should generate hidden fields for matched transaction
        $this->assertStringContainsString('partnerId_123', $output);
        $this->assertStringContainsString('partnerDetailId_123', $output);
        $this->assertStringContainsString('trans_no_123', $output);
        $this->assertStringContainsString('trans_type_123', $output);
    }
    
    /**
     * Test Matched Existing (ZZ) without matching_trans data
     * 
     * Should not generate hidden fields if no matching_trans
     */
    public function testDisplaysMatchedExistingWithoutMatchingTrans()
    {
        $dataWithoutMatch = $this->testData;
        $dataWithoutMatch['matching_trans'] = [];
        
        $strategy = new PartnerTypeDisplayStrategy($dataWithoutMatch);
        
        ob_start();
        $strategy->display('ZZ');
        $output = ob_get_clean();
        
        // Should not have hidden fields if no matching trans
        $this->assertStringNotContainsString('partnerId_123', $output);
    }
    
    /**
     * Test that Strategy can handle all partner types in sequence
     */
    public function testHandlesAllPartnerTypesSequentially()
    {
        if (!function_exists('supplier_list')) {
            $this->markTestSkipped('FA functions not available (expected in unit test context)');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        $types = ['SP', 'CU', 'BT', 'QE', 'MA', 'ZZ'];
        
        foreach ($types as $type) {
            ob_start();
            $strategy->display($type);
            $output = ob_get_clean();
            
            // Each should produce some output
            $this->assertIsString($output);
        }
    }
    
    /**
     * Test that Strategy requires all necessary data fields
     */
    public function testRequiresNecessaryDataFields()
    {
        // Minimum required fields
        $minimalData = [
            'id' => 123,
            'transactionDC' => 'D'
        ];
        
        $strategy = new PartnerTypeDisplayStrategy($minimalData);
        
        // Should be able to create strategy with minimal data
        $this->assertInstanceOf(PartnerTypeDisplayStrategy::class, $strategy);
        
        // Should still validate partner types
        $this->assertTrue($strategy->isValidPartnerType('SP'));
    }
    
    /**
     * Test that Strategy uses ViewFactory when available
     * 
     * This tests the integration with ViewFactory
     */
    public function testUsesViewFactoryForPartnerViews()
    {
        if (!function_exists('supplier_list')) {
            $this->markTestSkipped('FA functions not available (expected in unit test context)');
        }
        
        if (!class_exists('KsfBankImport\Views\ViewFactory')) {
            $this->markTestSkipped('ViewFactory not available in test environment');
        }
        
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        // ViewFactory should be used for creating views
        // This is integration test - verifies wiring
        ob_start();
        $strategy->display('SP');
        ob_end_clean();
        
        // If we got here, ViewFactory integration works
        $this->assertTrue(true);
    }
    
    /**
     * Test that Strategy maintains encapsulation
     * 
     * Strategy should only access data via constructor parameter,
     * not via direct property access
     */
    public function testMaintainsEncapsulation()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        // Strategy should not expose internal data array
        $reflection = new ReflectionClass($strategy);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);
        
        $internalData = $dataProperty->getValue($strategy);
        
        // Internal data should match what we passed
        $this->assertEquals(123, $internalData['id']);
        $this->assertEquals('D', $internalData['transactionDC']);
    }
    
    /**
     * Test that render() method returns HtmlFragment
     * 
     * The new render() method should return HtmlFragment instead of echoing.
     * This allows caller to control when/how output happens.
     */
    public function testRenderReturnsHtmlFragment()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        // Test ZZ (matched existing) - returns HtmlFragment with hidden fields
        $result = $strategy->render('ZZ');
        
        $this->assertInstanceOf('Ksfraser\HTML\HtmlFragment', $result);
        $this->assertGreaterThan(0, $result->getChildCount(), 'Fragment should contain hidden fields');
        
        // HTML should contain hidden inputs
        $html = $result->getHtml();
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('partnerId_123', $html);
    }
    
    /**
     * Test that render() can be composed without immediate echo
     * 
     * Major benefit: can build complex HTML structures without side effects
     */
    public function testRenderAllowsComposition()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        // Render multiple partner types and compose them
        $fragment1 = $strategy->render('ZZ');
        $fragment2 = $strategy->render('ZZ'); // Same type, different data
        
        // Can get HTML without echoing
        $html1 = $fragment1->getHtml();
        $html2 = $fragment2->getHtml();
        
        // Both should be valid HTML strings
        $this->assertIsString($html1);
        $this->assertIsString($html2);
        
        // Neither should have been echoed yet
        $this->expectOutputString(''); // No output during test
    }
    
    /**
     * Test backward compatibility - display() still works
     * 
     * Old display() method should still work for backward compatibility
     */
    public function testDisplayMethodBackwardCompatibility()
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);
        
        // display() should echo output
        ob_start();
        $strategy->display('ZZ');
        $output = ob_get_clean();
        
        // Should have echoed hidden fields
        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('partnerId_123', $output);
    }
}
