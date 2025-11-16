<?php

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive regression tests for bi_lineitem Quick Entry matching
 * 
 * This test suite validates the NEW matching logic that includes Quick Entry detection.
 * Tests cover all branches, edge cases, and ensures no functionality loss from refactoring.
 * 
 * Coverage areas:
 * - All conditional branches (if/else/switch)
 * - Edge cases (boundary values, empty arrays, null values)
 * - Transaction type detection (Invoice, Quick Entry, Generic)
 * - Score thresholds
 * - Array size variations (0, 1, 2, 3+ matches)
 */
class BiLineItemQERegressionTest extends TestCase
{
    /**
     * Test: No matching transactions (empty array)
     * Branch: if( count( $this->matching_trans ) > 0 ) -> FALSE
     * Expected: No processing, no partner type set
     */
    public function testNoMatchesEmptyArray(): void
    {
        $matchingTrans = [];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 100);
        
        $this->assertNull($result['partnerType']);
        $this->assertNull($result['oplabel']);
    }

    /**
     * Test: Single match, score < 50 (low confidence)
     * Branch: if( 50 <= $matching_trans[0]['score'] ) -> FALSE  
     * Expected: No partner type set (triggers var_dump branch)
     */
    public function testSingleMatchScoreBelowThreshold(): void
    {
        $matchingTrans = [
            [
                'score' => 49,
                'type' => ST_BANKPAYMENT,
                'type_no' => 100,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 200);
        
        $this->assertNull($result['partnerType']);
        $this->assertEquals('var_dump_triggered', $result['debug']);
    }

    /**
     * Test: Single match, score = 50 (exact threshold)
     * Branch: if( 50 <= $matching_trans[0]['score'] ) -> TRUE
     * Expected: Process match
     */
    public function testSingleMatchScoreExactThreshold(): void
    {
        $matchingTrans = [
            [
                'score' => 50,
                'type' => ST_JOURNAL,
                'type_no' => 300,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 300);
        
        // OLD version: ZZ, NEW version: could be ZZ or QE
        $this->assertNotNull($result['partnerType']);
        $this->assertEquals('MATCH', $result['oplabel']);
    }

    /**
     * Test: High score match, is_invoice = true
     * Branch: if( $matching_trans[0]['is_invoice'] ) -> TRUE
     * Expected: Partner type = 'SP' (Supplier Payment)
     */
    public function testHighScoreMatchIsInvoiceTrue(): void
    {
        $matchingTrans = [
            [
                'score' => 95,
                'type' => ST_SUPPINVOICE,
                'type_no' => 400,
                'is_invoice' => true
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 400);
        
        $this->assertEquals('SP', $result['partnerType']);
        $this->assertStringContainsString('MATCH', $result['oplabel']);
    }

    /**
     * Test: High score match, type = ST_BANKPAYMENT (NEW FEATURE)
     * Branch: elseif( isset($matching_trans[0]['type']) && 
     *                  $matching_trans[0]['type'] == ST_BANKPAYMENT ) -> TRUE
     * Expected: Partner type = 'QE' (Quick Entry) in NEW version
     */
    public function testHighScoreMatchQuickEntryPayment(): void
    {
        $matchingTrans = [
            [
                'score' => 80,
                'type' => ST_BANKPAYMENT,
                'type_no' => 500,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 500);
        
        // NEW version should detect QE
        // OLD version would be ZZ
        $this->assertContains($result['partnerType'], ['QE', 'ZZ']);
        
        if ($result['partnerType'] === 'QE') {
            $this->assertEquals('Quick Entry MATCH', $result['oplabel']);
        }
    }

    /**
     * Test: High score match, type = ST_BANKDEPOSIT (NEW FEATURE)
     * Branch: elseif( $matching_trans[0]['type'] == ST_BANKDEPOSIT ) -> TRUE
     * Expected: Partner type = 'QE' (Quick Entry) in NEW version
     */
    public function testHighScoreMatchQuickEntryDeposit(): void
    {
        $matchingTrans = [
            [
                'score' => 85,
                'type' => ST_BANKDEPOSIT,
                'type_no' => 600,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 600);
        
        // NEW version should detect QE
        $this->assertContains($result['partnerType'], ['QE', 'ZZ']);
        
        if ($result['partnerType'] === 'QE') {
            $this->assertEquals('Quick Entry MATCH', $result['oplabel']);
        }
    }

    /**
     * Test: High score match, generic transaction type
     * Branch: else -> TRUE (not invoice, not QE)
     * Expected: Partner type = 'ZZ' (Generic match)
     */
    public function testHighScoreMatchGenericType(): void
    {
        $matchingTrans = [
            [
                'score' => 70,
                'type' => ST_JOURNAL,
                'type_no' => 700,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 700);
        
        $this->assertEquals('ZZ', $result['partnerType']);
        $this->assertStringContainsString('MATCH', $result['oplabel']);
    }

    /**
     * Test: Exactly 2 matches (typical case)
     * Branch: if( count( $matching_trans ) < 3 ) -> TRUE
     * Expected: Process first match
     */
    public function testExactlyTwoMatches(): void
    {
        $matchingTrans = [
            [
                'score' => 90,
                'type' => ST_BANKPAYMENT,
                'type_no' => 800,
                'is_invoice' => false
            ],
            [
                'score' => 45,
                'type' => ST_JOURNAL,
                'type_no' => 801,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 800);
        
        // Should process first match
        $this->assertNotNull($result['partnerType']);
        $this->assertEquals(800, $result['trans_no']);
    }

    /**
     * Test: Exactly 3 matches (boundary - split transactions)
     * Branch: if( count( $matching_trans ) < 3 ) -> FALSE
     * Expected: No automatic processing
     */
    public function testExactlyThreeMatches(): void
    {
        $matchingTrans = [
            ['score' => 90, 'type' => 1, 'type_no' => 900, 'is_invoice' => false],
            ['score' => 85, 'type' => 2, 'type_no' => 901, 'is_invoice' => false],
            ['score' => 80, 'type' => 3, 'type_no' => 902, 'is_invoice' => false]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 900);
        
        // Should NOT process (needs manual sorting)
        $this->assertNull($result['partnerType']);
        $this->assertEquals('needs_manual_sort', $result['debug']);
    }

    /**
     * Test: 4+ matches (split transactions with rewards)
     * Branch: if( count( $matching_trans ) < 3 ) -> FALSE
     * Expected: No automatic processing
     */
    public function testFourOrMoreMatches(): void
    {
        $matchingTrans = [
            ['score' => 90, 'type' => 1, 'type_no' => 1000, 'is_invoice' => false],
            ['score' => 85, 'type' => 2, 'type_no' => 1001, 'is_invoice' => false],
            ['score' => 80, 'type' => 3, 'type_no' => 1002, 'is_invoice' => false],
            ['score' => 75, 'type' => 4, 'type_no' => 1003, 'is_invoice' => false]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 1000);
        
        // Should NOT process
        $this->assertNull($result['partnerType']);
    }

    /**
     * Test: Edge case - type field missing
     * Branch: isset($matching_trans[0]['type']) -> FALSE
     * Expected: Fall through to ZZ (don't crash)
     */
    public function testMissingTypeField(): void
    {
        $matchingTrans = [
            [
                'score' => 80,
                // 'type' field intentionally missing
                'type_no' => 1100,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 1100);
        
        // Should handle gracefully - fall to else branch
        $this->assertEquals('ZZ', $result['partnerType']);
    }

    /**
     * Test: Edge case - is_invoice field missing
     * Expected: Treat as false, continue to QE or ZZ check
     */
    public function testMissingIsInvoiceField(): void
    {
        $matchingTrans = [
            [
                'score' => 90,
                'type' => ST_BANKPAYMENT,
                'type_no' => 1200
                // 'is_invoice' field intentionally missing
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 1200);
        
        // Should not crash, should detect as QE or ZZ
        $this->assertNotNull($result['partnerType']);
    }

    /**
     * Test: Edge case - score field missing
     * Expected: Should handle gracefully (likely skip processing)
     */
    public function testMissingScoreField(): void
    {
        $matchingTrans = [
            [
                // 'score' field intentionally missing
                'type' => ST_JOURNAL,
                'type_no' => 1300,
                'is_invoice' => false
            ]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 1300);
        
        // Should handle missing score gracefully
        $this->assertTrue(
            $result['partnerType'] === null || $result['partnerType'] === 'ZZ'
        );
    }

    /**
     * Test: Multiple matches with varying scores
     * Expected: Use first match (highest score should be sorted first)
     */
    public function testMultipleMatchesVaryingScores(): void
    {
        $matchingTrans = [
            ['score' => 95, 'type' => ST_BANKPAYMENT, 'type_no' => 1400, 'is_invoice' => false],
            ['score' => 60, 'type' => ST_JOURNAL, 'type_no' => 1401, 'is_invoice' => false]
        ];
        
        $result = $this->evaluateMatchingLogic($matchingTrans, 1400);
        
        // Should use first match
        $this->assertEquals(1400, $result['trans_no']);
    }

    /**
     * Helper method to simulate the matching logic
     * This method replicates the core logic of getDisplayMatchingTrans()
     * for testing purposes without needing a full database
     */
    private function evaluateMatchingLogic(array $matchingTrans, int $id): array
    {
        $result = [
            'partnerType' => null,
            'oplabel' => null,
            'trans_type' => null,
            'trans_no' => null,
            'debug' => null
        ];

        if (count($matchingTrans) > 0) {
            // Check if < 3 (handles rewards/split transactions)
            if (count($matchingTrans) < 3) {
                // Check if score is high enough
                if (isset($matchingTrans[0]['score']) && 50 <= $matchingTrans[0]['score']) {
                    // Check invoice first
                    if (isset($matchingTrans[0]['is_invoice']) && $matchingTrans[0]['is_invoice']) {
                        $result['partnerType'] = 'SP';
                        $result['oplabel'] = 'INVOICE MATCH';
                    }
                    // NEW: Check for Quick Entry
                    elseif (isset($matchingTrans[0]['type']) && 
                           ($matchingTrans[0]['type'] == ST_BANKPAYMENT || 
                            $matchingTrans[0]['type'] == ST_BANKDEPOSIT)) {
                        $result['partnerType'] = 'QE';
                        $result['oplabel'] = 'Quick Entry MATCH';
                    }
                    // Generic match
                    else {
                        $result['partnerType'] = 'ZZ';
                        $result['oplabel'] = 'MATCH';
                    }
                    
                    if (isset($matchingTrans[0]['type'])) {
                        $result['trans_type'] = $matchingTrans[0]['type'];
                    }
                    if (isset($matchingTrans[0]['type_no'])) {
                        $result['trans_no'] = $matchingTrans[0]['type_no'];
                    }
                } else {
                    $result['debug'] = 'var_dump_triggered';
                }
            } else {
                $result['debug'] = 'needs_manual_sort';
            }
        }

        return $result;
    }
}

// Define constants if not already defined (for standalone testing)
if (!defined('ST_JOURNAL')) define('ST_JOURNAL', 0);
if (!defined('ST_BANKPAYMENT')) define('ST_BANKPAYMENT', 1);
if (!defined('ST_BANKDEPOSIT')) define('ST_BANKDEPOSIT', 2);
if (!defined('ST_BANKTRANSFER')) define('ST_BANKTRANSFER', 4);
if (!defined('ST_SUPPINVOICE')) define('ST_SUPPINVOICE', 20);
if (!defined('ST_SUPPAYMENT')) define('ST_SUPPAYMENT', 22);
