<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for bi_lineitem matching logic
 * Tests all branches, edge cases, and conditions in getDisplayMatchingTrans()
 */
class BiLineItemMatchingTest extends TestCase
{
    private $lineItem;
    private $mockDb;

    protected function setUp(): void
    {
        // Mock $_POST to avoid undefined index warnings
        if (!isset($_POST)) {
            $_POST = [];
        }
        
        // Include the class file
        require_once __DIR__ . '/../../class.bi_lineitem.php';
        
        // Create a mock bi_lineitem instance
        $this->lineItem = $this->getMockBuilder('bi_lineitem')
            ->disableOriginalConstructor()
            ->onlyMethods(['findMatchingExistingJE'])
            ->getMock();
        
        // Set a test ID
        $this->lineItem->id = 123;
    }

    protected function tearDown(): void
    {
        // Clean up $_POST
        if (isset($_POST['partnerType'])) {
            unset($_POST['partnerType']);
        }
    }

    /**
     * Test: No matching transactions found
     * Expected: No partner type set, no processing
     */
    public function testNoMatchingTransactions(): void
    {
        $this->lineItem->matching_trans = [];
        
        // Configure mock to set matching_trans
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                $this->lineItem->matching_trans = [];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Assert no partner type was set
        $this->assertArrayNotHasKey($this->lineItem->id, $_POST['partnerType'] ?? []);
    }

    /**
     * Test: Single match with low score (< 50)
     * Expected: No partner type set (score too low)
     */
    public function testSingleMatchLowScore(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 30,
                'type' => 1,
                'type_no' => 100,
                'is_invoice' => false
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        ob_start(); // Capture any output from var_dump
        $this->lineItem->getDisplayMatchingTrans();
        $output = ob_get_clean();
        
        // Assert no partner type was set due to low score
        $this->assertArrayNotHasKey($this->lineItem->id, $_POST['partnerType'] ?? []);
        
        // Should trigger var_dump for low score branch
        $this->assertNotEmpty($output);
    }

    /**
     * Test: Single match with high score (>= 50) and is_invoice = true
     * Expected: Partner type set to 'SP' (Supplier Payment)
     */
    public function testSingleMatchHighScoreInvoice(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 75,
                'type' => ST_SUPPINVOICE,
                'type_no' => 100,
                'is_invoice' => true
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Assert partner type set to SP
        $this->assertEquals('SP', $_POST['partnerType'][$this->lineItem->id]);
        $this->assertEquals("MATCH", $this->lineItem->oplabel);
    }

    /**
     * Test: Single match with high score (>= 50) and is_invoice = false
     * Expected: Partner type set to 'ZZ' (Generic match) in OLD code
     */
    public function testSingleMatchHighScoreNotInvoice(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 60,
                'type' => ST_JOURNAL,
                'type_no' => 200,
                'is_invoice' => false
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // In OLD version: should be 'ZZ'
        $this->assertEquals('ZZ', $_POST['partnerType'][$this->lineItem->id]);
        $this->assertEquals("MATCH", $this->lineItem->oplabel);
    }

    /**
     * Test: Exactly 2 matches (typical case) with high score
     * Expected: Process first match
     */
    public function testTwoMatchesHighScore(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 85,
                'type' => ST_BANKPAYMENT,
                'type_no' => 300,
                'is_invoice' => false
            ],
            [
                'score' => 40,
                'type' => ST_BANKDEPOSIT,
                'type_no' => 301,
                'is_invoice' => false
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should process first match with highest score
        $this->assertEquals('ZZ', $_POST['partnerType'][$this->lineItem->id]);
    }

    /**
     * Test: 3 or more matches (split transactions like rewards)
     * Expected: No automatic processing (needs manual sorting)
     */
    public function testThreeOrMoreMatches(): void
    {
        $this->lineItem->matching_trans = [
            ['score' => 90, 'type' => 1, 'type_no' => 100, 'is_invoice' => false],
            ['score' => 85, 'type' => 2, 'type_no' => 101, 'is_invoice' => false],
            ['score' => 80, 'type' => 3, 'type_no' => 102, 'is_invoice' => false]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should NOT set partner type (needs manual review)
        $this->assertArrayNotHasKey($this->lineItem->id, $_POST['partnerType'] ?? []);
    }

    /**
     * Test: Edge case - exactly score of 50 (boundary condition)
     * Expected: Should process (>= 50 condition)
     */
    public function testScoreExactly50(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 50,
                'type' => ST_JOURNAL,
                'type_no' => 400,
                'is_invoice' => false
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should process with score = 50
        $this->assertEquals('ZZ', $_POST['partnerType'][$this->lineItem->id]);
    }

    /**
     * Test: Edge case - score of 49 (just below threshold)
     * Expected: Should NOT process
     */
    public function testScoreJustBelow50(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 49,
                'type' => ST_JOURNAL,
                'type_no' => 500,
                'is_invoice' => false
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        ob_start();
        $this->lineItem->getDisplayMatchingTrans();
        $output = ob_get_clean();
        
        // Should NOT process with score = 49
        $this->assertArrayNotHasKey($this->lineItem->id, $_POST['partnerType'] ?? []);
    }

    /**
     * Test: Edge case - exactly 3 matches (boundary)
     * Expected: Should NOT auto-process
     */
    public function testExactlyThreeMatches(): void
    {
        $this->lineItem->matching_trans = [
            ['score' => 90, 'type' => 1, 'type_no' => 100, 'is_invoice' => false],
            ['score' => 85, 'type' => 2, 'type_no' => 101, 'is_invoice' => false],
            ['score' => 80, 'type' => 3, 'type_no' => 102, 'is_invoice' => false]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should NOT process (count >= 3)
        $this->assertArrayNotHasKey($this->lineItem->id, $_POST['partnerType'] ?? []);
    }

    /**
     * Test: Verify hidden fields are set for high-score match
     * Expected: Hidden fields for trans_type and trans_no
     */
    public function testHiddenFieldsSetForMatch(): void
    {
        $this->lineItem->matching_trans = [
            [
                'score' => 100,
                'type' => ST_SUPPAYMENT,
                'type_no' => 999,
                'is_invoice' => true
            ]
        ];
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                // Already set above
            });
        
        // Capture hidden() function calls
        $GLOBALS['hidden_fields'] = [];
        function hidden($name, $value) {
            $GLOBALS['hidden_fields'][$name] = $value;
        }
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Verify hidden fields were set
        $this->assertEquals(ST_SUPPAYMENT, $GLOBALS['hidden_fields']["trans_type_{$this->lineItem->id}"] ?? null);
        $this->assertEquals(999, $GLOBALS['hidden_fields']["trans_no_{$this->lineItem->id}"] ?? null);
    }
}
