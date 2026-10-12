<?php
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// Define constants if not already defined
if (!defined('ST_JOURNAL')) {
    define('ST_JOURNAL', 0);
}
if (!defined('ST_SUPPINVOICE')) {
    define('ST_SUPPINVOICE', 20);
}
if (!defined('ST_BANKPAYMENT')) {
    define('ST_BANKPAYMENT', 1);
}
if (!defined('ST_BANKDEPOSIT')) {
    define('ST_BANKDEPOSIT', 2);
}
if (!defined('ST_SUPPAYMENT')) {
    define('ST_SUPPAYMENT', 22);
}
// USE_V2_PARTNER_VIEWS is defined in class.bi_lineitem.php

/**
 * Comprehensive tests for bi_lineitem matching logic
 * Tests all branches, edge cases, and conditions in getDisplayMatchingTrans()
 */
class BiLineItemMatchingTest extends TestCase
{
    private $lineItem;

    protected function setUp(): void
    {
        // Mock $_POST to avoid undefined index warnings
        if (!isset($_POST)) {
            $_POST = [];
        }
        
        // Include the class file
        require_once __DIR__ . '/../../class.bi_lineitem.php';
        
        // Create a mock bi_lineitem instance that mocks findMatchingExistingJE
        $this->lineItem = $this->getMockBuilder(\bi_lineitem::class)
            ->setMethods(['findMatchingExistingJE'])
            ->enableOriginalConstructor()
            ->getMock(); 
        
        // Set a test ID
        $this->lineItem->setId(123);
    }

    protected function tearDown(): void
    {
        // Clean up $_POST for partnerType
        $key = "partnerType[{$this->lineItem->getId()}]";
        if (isset($_POST[$key])) {
            unset($_POST[$key]);
        }
    }

    /**
     * Test: No matching transactions found
     * Expected: No partner type set, no processing
     */
    public function testNoMatchingTransactions(): void
    {
        $this->lineItem->setMatchingTrans([]);
        
        // Configure mock to set matching_trans
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Assert no partner type was set
        $this->assertNull($this->lineItem->getFormData()->getPartnerType());
    }

    /**
     * Test: Single match with low score (< 50)
     * Expected: No partner type set (score too low)
     */
    public function testSingleMatchLowScore(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 30,
                'type' => 1,
                'type_no' => 100,
                'is_invoice' => false
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 30,
                        'type' => 1,
                        'type_no' => 100,
                        'is_invoice' => false
                    ]
                ];
            });
        
        ob_start(); // Capture any output from var_dump
        $this->lineItem->getDisplayMatchingTrans();
        $output = ob_get_clean();
        
        // Assert no partner type was set due to low score
        $this->assertNull($this->lineItem->getFormData()->getPartnerType());
        
        // Should trigger var_dump for low score branch
        $this->assertNotEmpty($output);
    }

    /**
     * Test: Single match with high score (>= 50) and is_invoice = true
     * Expected: Partner type set to 'SP' (Supplier Payment)
     */
    public function testSingleMatchHighScoreInvoice(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 75,
                'type' => \ST_SUPPINVOICE,
                'type_no' => 100,
                'is_invoice' => true
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 75,
                        'type' => \ST_SUPPINVOICE,
                        'type_no' => 100,
                        'is_invoice' => true
                    ]
                ];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Assert partner type set to SP
        $this->assertEquals('SP', $this->lineItem->getFormData()->getPartnerType());
        $this->assertEquals("MATCH", $this->lineItem->getOplabel());
    }

    /**
     * Test: Single match with high score (>= 50) and is_invoice = false
     * Expected: Partner type set to 'ZZ' (Generic match) in OLD code
     */
    public function testSingleMatchHighScoreNotInvoice(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 60,
                'type' => \ST_JOURNAL,
                'type_no' => 200,
                'is_invoice' => false
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 60,
                        'type' => \ST_JOURNAL,
                        'type_no' => 200,
                        'is_invoice' => false
                    ]
                ];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // In OLD version: should be 'ZZ'
        $this->assertEquals('ZZ', $this->lineItem->getFormData()->getPartnerType());
        $this->assertEquals("MATCH", $this->lineItem->getOplabel());
    }

    /**
     * Test: Exactly 2 matches (typical case) with high score
     * Expected: Process first match
     */
    public function testTwoMatchesHighScore(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 85,
                'type' => \ST_BANKPAYMENT,
                'type_no' => 300,
                'is_invoice' => false
            ],
            [
                'score' => 40,
                'type' => \ST_BANKDEPOSIT,
                'type_no' => 301,
                'is_invoice' => false
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 85,
                        'type' => \ST_BANKPAYMENT,
                        'type_no' => 300,
                        'is_invoice' => false
                    ],
                    [
                        'score' => 40,
                        'type' => \ST_BANKDEPOSIT,
                        'type_no' => 301,
                        'is_invoice' => false
                    ]
                ];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should process first match with highest score
        $this->assertEquals('ZZ', $this->lineItem->getFormData()->getPartnerType());
    }

    /**
     * Test: 3 or more matches (split transactions like rewards)
     * Expected: No automatic processing (needs manual sorting)
     */
    public function testThreeOrMoreMatches(): void
    {
        $this->lineItem->setMatchingTrans([
            ['score' => 90, 'type' => 1, 'type_no' => 100, 'is_invoice' => false],
            ['score' => 85, 'type' => 2, 'type_no' => 101, 'is_invoice' => false],
            ['score' => 80, 'type' => 3, 'type_no' => 102, 'is_invoice' => false]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    ['score' => 90, 'type' => 1, 'type_no' => 100, 'is_invoice' => false],
                    ['score' => 85, 'type' => 2, 'type_no' => 101, 'is_invoice' => false],
                    ['score' => 80, 'type' => 3, 'type_no' => 102, 'is_invoice' => false]
                ];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should NOT set partner type (needs manual review)
        $this->assertNull($this->lineItem->getFormData()->getPartnerType());
    }

    /**
     * Test: Edge case - exactly score of 50 (boundary condition)
     * Expected: Should process (>= 50 condition)
     */
    public function testScoreExactly50(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 50,
                'type' => \ST_JOURNAL,
                'type_no' => 400,
                'is_invoice' => false
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 50,
                        'type' => \ST_JOURNAL,
                        'type_no' => 400,
                        'is_invoice' => false
                    ]
                ];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should process with score = 50
        $this->assertEquals('ZZ', $this->lineItem->getFormData()->getPartnerType());
    }

    /**
     * Test: Edge case - score of 49 (just below threshold)
     * Expected: Should NOT process
     */
    public function testScoreJustBelow50(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 49,
                'type' => \ST_JOURNAL,
                'type_no' => 500,
                'is_invoice' => false
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 49,
                        'type' => \ST_JOURNAL,
                        'type_no' => 500,
                        'is_invoice' => false
                    ]
                ];
            });
        
        ob_start();
        $this->lineItem->getDisplayMatchingTrans();
        $output = ob_get_clean();
        
        // Should NOT process with score = 49
        $this->assertNull($this->lineItem->getFormData()->getPartnerType());
    }

    /**
     * Test: Edge case - exactly 3 matches (boundary)
     * Expected: Should NOT auto-process
     */
    public function testExactlyThreeMatches(): void
    {
        $this->lineItem->setMatchingTrans([
            ['score' => 90, 'type' => 1, 'type_no' => 100, 'is_invoice' => false],
            ['score' => 85, 'type' => 2, 'type_no' => 101, 'is_invoice' => false],
            ['score' => 80, 'type' => 3, 'type_no' => 102, 'is_invoice' => false]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    ['score' => 90, 'type' => 1, 'type_no' => 100, 'is_invoice' => false],
                    ['score' => 85, 'type' => 2, 'type_no' => 101, 'is_invoice' => false],
                    ['score' => 80, 'type' => 3, 'type_no' => 102, 'is_invoice' => false]
                ];
            });
        
        $this->lineItem->getDisplayMatchingTrans();
        
        // Should NOT process (count >= 3)
        $this->assertNull($this->lineItem->getFormData()->getPartnerType());
    }

    /**
     * Test: Verify hidden fields are set for high-score match
     * Expected: Hidden fields for trans_type and trans_no are output
     */
    public function testHiddenFieldsSetForMatch(): void
    {
        $this->lineItem->setMatchingTrans([
            [
                'score' => 100,
                'type' => \ST_SUPPAYMENT,
                'type_no' => 999,
                'is_invoice' => true
            ]
        ]);
        
        $this->lineItem->method('findMatchingExistingJE')
            ->willReturnCallback(function() {
                return [
                    [
                        'score' => 100,
                        'type' => \ST_SUPPAYMENT,
                        'type_no' => 999,
                        'is_invoice' => true
                    ]
                ];
            });
        
        // Capture output from getDisplayMatchingTrans (which echoes the hidden fields)
        ob_start();
        $this->lineItem->getDisplayMatchingTrans();
        $output = ob_get_clean();
        
        // Verify hidden fields were output
        $this->assertStringContainsString('trans_type_123', $output);
        $this->assertStringContainsString('value="22"', $output);
        $this->assertStringContainsString('trans_no_123', $output);
        $this->assertStringContainsString('value="999"', $output);
    }
}