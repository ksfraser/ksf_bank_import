<?php

/**
 * Test for bi_lineitem Display Methods Refactoring
 * 
 * RTDD (Refactor Test-Driven Development):
 * 1. Test existing behavior (display_left/display_right echo HTML)
 * 2. Add tests for new behavior (getLeftHtml/getRightHtml return HTML)
 * 3. New tests fail (methods don't exist yet)
 * 4. Implement new methods (tests pass)
 * 5. Refactor old methods to use new methods
 * 6. All tests still pass (no breaking changes)
 *
 * @package    KsfBankImport
 * @subpackage Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\PartnerTypeConstants;

/**
 * Test bi_lineitem Display Methods
 *
 * Tests verify that:
 * - getHtml() returns complete HTML string
 * - getLeftHtml() returns left column HTML string
 * - getRightHtml() returns right column HTML string (to be implemented)
 * - display() echoes the same HTML that getHtml() returns
 * - display_left() echoes same HTML that getLeftHtml() returns
 * - display_right() echoes same HTML that getRightHtml() returns
 * 
 * NOTE: Due to complex dependencies, this test focuses on method signatures
 * and behavior contracts rather than full integration testing.
 */
class BiLineItemDisplayTest extends TestCase
{
    /**
     * Sample transaction data for testing
     *
     * @var array<string, mixed>
     */
    private array $sampleTransaction;

    /**
     * Sample vendor list for testing
     *
     * @var array<int, string>
     */
    private array $sampleVendorList;

    /**
     * Sample optypes array
     *
     * @var array<string, string>
     */
    private array $sampleOptypes;

    /**
     * Check if bi_lineitem class file exists
     */
    private function classFileExists(): bool
    {
        return file_exists(__DIR__ . '/../../class.bi_lineitem.php');
    }
    
    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!$this->classFileExists()) {
            $this->markTestSkipped('class.bi_lineitem.php not found');
        }
        
        // Don't require the file - it has too many dependencies
        // We'll test method existence via reflection on production code
        
        // Minimal transaction data required by bi_lineitem
        $this->sampleTransaction = [
            'transactionDC' => 'C',
            'memo' => 'Test Transaction',
            'our_account' => '1001',
            'valueTimestamp' => '2025-10-21',
            'entryTimestamp' => '2025-10-21',
            'accountName' => 'Test Account',
            'transactionTitle' => 'Test Title',
            'transactionCode' => 'TC001',
            'transactionCodeDesc' => 'Test Code',
            'currency' => 'USD',
            'status' => 0, // Unsettled
            'id' => 123,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'transactionAmount' => 100.50,
            'transactionType' => 'TRN'
        ];

        // Sample vendor list
        $this->sampleVendorList = [
            1 => 'Vendor One',
            2 => 'Vendor Two',
        ];

        // Sample optypes
        $this->sampleOptypes = PartnerTypeConstants::getAll();
    }

    /**
     * Test that getHtml() method exists and returns a string
     *
     * @test
     */
    public function getHtml_exists_and_returns_string(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $this->assertTrue(
            method_exists($lineItem, 'getHtml'),
            'getHtml() method must exist'
        );

        $html = $lineItem->getHtml();

        $this->assertIsString($html, 'getHtml() must return a string');
        $this->assertNotEmpty($html, 'getHtml() must return non-empty HTML');
    }

    /**
     * Test that getLeftHtml() method exists and returns a string
     *
     * @test
     */
    public function getLeftHtml_exists_and_returns_string(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $this->assertTrue(
            method_exists($lineItem, 'getLeftHtml'),
            'getLeftHtml() method must exist'
        );

        $html = $lineItem->getLeftHtml();

        $this->assertIsString($html, 'getLeftHtml() must return a string');
        $this->assertNotEmpty($html, 'getLeftHtml() must return non-empty HTML');
    }

    /**
     * Test that getRightHtml() method exists and returns a string
     *
     * This test will FAIL initially - that's expected in RTDD!
     * We'll implement getRightHtml() to make it pass.
     *
     * @test
     */
    public function getRightHtml_exists_and_returns_string(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $this->assertTrue(
            method_exists($lineItem, 'getRightHtml'),
            'getRightHtml() method must exist'
        );

        $html = $lineItem->getRightHtml();

        $this->assertIsString($html, 'getRightHtml() must return a string');
        $this->assertNotEmpty($html, 'getRightHtml() must return non-empty HTML');
    }

    /**
     * Test that display() echoes the same output as getHtml() returns
     *
     * @test
     */
    public function display_echoes_same_output_as_getHtml_returns(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        // Get the HTML string
        $expectedHtml = $lineItem->getHtml();

        // Capture what display() echoes
        ob_start();
        $lineItem->display();
        $actualHtml = ob_get_clean();

        $this->assertSame(
            $expectedHtml,
            $actualHtml,
            'display() must echo exactly what getHtml() returns'
        );
    }

    /**
     * Test that getHtml() combines left and right HTML
     *
     * @test
     */
    public function getHtml_combines_left_and_right_html(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $leftHtml = $lineItem->getLeftHtml();
        $rightHtml = $lineItem->getRightHtml();
        $fullHtml = $lineItem->getHtml();

        // Full HTML should contain both left and right
        $this->assertStringContainsString(
            $leftHtml,
            $fullHtml,
            'getHtml() must include left HTML'
        );

        $this->assertStringContainsString(
            $rightHtml,
            $fullHtml,
            'getHtml() must include right HTML'
        );

        // Full HTML should be concatenation of left + right
        $this->assertSame(
            $leftHtml . $rightHtml,
            $fullHtml,
            'getHtml() should be leftHtml + rightHtml'
        );
    }

    /**
     * Test that getLeftHtml() returns valid HTML table structure
     *
     * @test
     */
    public function getLeftHtml_returns_valid_table_structure(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $html = $lineItem->getLeftHtml();

        // Should contain transaction date
        $this->assertStringContainsString(
            $this->sampleTransaction['valueTimestamp'],
            $html,
            'Left HTML should contain transaction date'
        );

        // Should contain table structures
        $this->assertStringContainsString('<td', $html);
        $this->assertStringContainsString('</td>', $html);
    }

    /**
     * Test that getRightHtml() returns valid HTML table structure
     *
     * This test will FAIL initially until getRightHtml() is implemented.
     *
     * @test
     */
    public function getRightHtml_returns_valid_table_structure(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $html = $lineItem->getRightHtml();

        // Should contain table structures
        $this->assertStringContainsString('<td', $html);
        $this->assertStringContainsString('</td>', $html);
        
        // Should contain partner type selector (for unsettled transactions)
        $this->assertStringContainsString('partnerType', $html);
    }

    /**
     * Test that display_left() echoes same output as getLeftHtml() returns
     *
     * This verifies backward compatibility after refactoring.
     * Currently display_left() echoes directly. After refactoring,
     * it should call getLeftHtml() and echo the result.
     *
     * @test
     */
    public function display_left_echoes_same_as_getLeftHtml_returns(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        // Get the HTML string from getLeftHtml()
        $expectedHtml = $lineItem->getLeftHtml();

        // Capture what display_left() echoes
        ob_start();
        $lineItem->display_left();
        $actualHtml = ob_get_clean();

        $this->assertSame(
            $expectedHtml,
            $actualHtml,
            'display_left() must echo exactly what getLeftHtml() returns'
        );
    }

    /**
     * Test that display_right() echoes same output as getRightHtml() returns
     *
     * This test will FAIL initially until:
     * 1. getRightHtml() is implemented
     * 2. display_right() is refactored to use getRightHtml()
     *
     * @test
     */
    public function display_right_echoes_same_as_getRightHtml_returns(): void
    {
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        // Get the HTML string from getRightHtml()
        $expectedHtml = $lineItem->getRightHtml();

        // Capture what display_right() echoes
        ob_start();
        $lineItem->display_right();
        $actualHtml = ob_get_clean();

        $this->assertSame(
            $expectedHtml,
            $actualHtml,
            'display_right() must echo exactly what getRightHtml() returns'
        );
    }

    /**
     * Test for settled transaction (status = 1)
     *
     * @test
     */
    public function getRightHtml_handles_settled_transaction(): void
    {
        $settledTransaction = array_merge($this->sampleTransaction, ['status' => 1]);
        
        $lineItem = new \bi_lineitem(
            $settledTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $html = $lineItem->getRightHtml();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        
        // Settled transactions should show different content
        // (exact content depends on display_settled() method)
    }

    /**
     * Test for unsettled transaction (status = 0)
     *
     * @test
     */
    public function getRightHtml_handles_unsettled_transaction(): void
    {
        // Transaction is already unsettled in setUp (status = 0)
        $lineItem = new \bi_lineitem(
            $this->sampleTransaction,
            $this->sampleVendorList,
            $this->sampleOptypes
        );

        $html = $lineItem->getRightHtml();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        
        // Unsettled transactions should show partner type selector
        $this->assertStringContainsString('partnerType', $html);
    }
}
