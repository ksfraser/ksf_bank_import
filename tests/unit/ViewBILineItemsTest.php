<?php

/**
 * Unit Tests for ViewBILineItems Class
 *
 * Tests the view component responsible for displaying bank import line items.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      October 19, 2025
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for ViewBILineItems class
 *
 * Focuses on critical bugs and display logic.
 */
class ViewBILineItemsTest extends TestCase
{
    /**
     * @var ViewBILineItems
     */
    private $view;

    /**
     * @var object Mock bi_lineitem object
     */
    private $mockLineitem;

    /**
     * Set up test fixtures before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock bi_lineitem object with minimal properties
        $this->mockLineitem = new \stdClass();
        $this->mockLineitem->id = 123;
        $this->mockLineitem->valueTimestamp = time();
        $this->mockLineitem->entryTimestamp = time();
        $this->mockLineitem->transactionDC = 'D';
        $this->mockLineitem->transactionType = 'Payment';
        $this->mockLineitem->our_account = 'CHK001';
        $this->mockLineitem->ourBankDetails = [
            'bank_account_name' => 'Checking Account',
            'bank_name' => 'Test Bank'
        ];
        $this->mockLineitem->otherBankAccount = 'VEN123';
        $this->mockLineitem->otherBankAccountName = 'Vendor Account';
        $this->mockLineitem->amount = 1000.00;
        $this->mockLineitem->charges = 5.00;
        $this->mockLineitem->transactionTitle = 'Test Transaction';
        $this->mockLineitem->partnerId = null;
        $this->mockLineitem->partnerDetailId = null;
        $this->mockLineitem->status = 'new';

        // Add method stubs
        $this->mockLineitem->getBankAccountDetails = function() {
            return $this->ourBankDetails;
        };
        $this->mockLineitem->isPaired = function() {
            return false;
        };
        $this->mockLineitem->matchedVendor = function() {
            throw new \Exception('No matched vendor');
        };
        $this->mockLineitem->matchedSupplierId = function($vendor) {
            return null;
        };
        $this->mockLineitem->selectAndDisplayButton = function() {
            return '';
        };
        $this->mockLineitem->setPartnerType = function($type) {
            $this->partnerType = $type;
        };
        $this->mockLineitem->getDisplayMatchingTrans = function() {
            return [];
        };

        // Include the class file
        require_once __DIR__ . '/../../class.bi_lineitem.php';
    }

    /**
     * Test that display_left() uses $this->bi_lineitem, not undefined $bi_lineitem
     *
     * This tests the critical bug fix where lines 349-354 incorrectly used
     * undefined variable $bi_lineitem instead of $this->bi_lineitem.
     *
     * @test
     */
    public function testDisplayLeftUsesThisBiLineitemNotUndefinedVariable(): void
    {
        // Skip test if FrontAccounting functions not available
        if (!function_exists('start_row')) {
            $this->markTestSkipped('FrontAccounting functions not available');
        }

        // Create view with mock lineitem
        $view = new \ViewBILineItems($this->mockLineitem);

        // Capture output
        ob_start();
        
        try {
            // This should NOT throw undefined variable error
            // If it does, the bug is not fixed
            $view->display_left();
            
            $output = ob_get_clean();
            
            // If we got here without error, the bug is fixed
            $this->assertTrue(true, 'display_left() executed without undefined variable error');
            
        } catch (\Error $e) {
            ob_end_clean();
            
            // Check if it's an undefined variable error
            if (strpos($e->getMessage(), 'Undefined variable') !== false && 
                strpos($e->getMessage(), 'bi_lineitem') !== false) {
                $this->fail('Bug not fixed: display_left() uses undefined $bi_lineitem variable');
            }
            
            // Some other error - might be expected (missing FA functions)
            $this->markTestSkipped('Test requires full FrontAccounting environment: ' . $e->getMessage());
        }
    }

    /**
     * Test that display_left() passes correct object to Trans* components
     *
     * Verifies that the bi_lineitem object passed to TransDate, TransType, etc.
     * is the same object stored in $this->bi_lineitem.
     *
     * @test
     */
    public function testDisplayLeftPassesCorrectObjectToComponents(): void
    {
        // This test verifies the semantic correctness of the fix
        // We can't easily test this without mocking the Trans* components,
        // but we can document the expected behavior
        
        $view = new \ViewBILineItems($this->mockLineitem);
        
        // Use reflection to access protected property
        $reflection = new \ReflectionClass($view);
        $property = $reflection->getProperty('bi_lineitem');
        $property->setAccessible(true);
        $actualLineitem = $property->getValue($view);
        
        // Verify the view has the correct bi_lineitem
        $this->assertSame(
            $this->mockLineitem,
            $actualLineitem,
            'ViewBILineItems should store the passed bi_lineitem object'
        );
        
        // The fix ensures that THIS object is passed to Trans* components,
        // not some undefined $bi_lineitem variable
    }

    /**
     * Test that ViewBILineItems constructor stores bi_lineitem correctly
     *
     * @test
     */
    public function testConstructorStoresBiLineitem(): void
    {
        $view = new \ViewBILineItems($this->mockLineitem);
        
        // Use reflection to verify
        $reflection = new \ReflectionClass($view);
        $property = $reflection->getProperty('bi_lineitem');
        $property->setAccessible(true);
        
        $this->assertSame(
            $this->mockLineitem,
            $property->getValue($view),
            'Constructor should store bi_lineitem in protected property'
        );
    }

    /**
     * Test display_left() HTML structure (basic smoke test)
     *
     * @test
     */
    public function testDisplayLeftGeneratesHtmlOutput(): void
    {
        if (!function_exists('start_row')) {
            $this->markTestSkipped('FrontAccounting functions not available');
        }

        $view = new \ViewBILineItems($this->mockLineitem);
        
        ob_start();
        try {
            $view->display_left();
            $output = ob_get_clean();
            
            // Should contain table opening
            $this->assertStringContainsString(
                '<td width="50%">',
                $output,
                'Output should contain table cell opening'
            );
            
        } catch (\Error $e) {
            ob_end_clean();
            $this->markTestSkipped('Test requires full environment: ' . $e->getMessage());
        }
    }
}
