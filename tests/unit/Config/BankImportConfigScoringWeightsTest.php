<?php

/**
 * BankImportConfig Scoring Weights Test Suite
 *
 * Tests configuration management for scoring rule weights:
 * - Reading/writing individual weights
 * - Validation of weight values (must be positive)
 * - Batch operations and defaults
 * - Integration with getAllSettings() and resetToDefaults()
 *
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::getScoringRecencyWeight
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::getScoringAmountWeight
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::getScoringTypeWeight
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::setScoringRecencyWeight
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::setScoringAmountWeight
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::setScoringTypeWeight
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::getScoringWeights
 * @covers \Ksfraser\FaBankImport\Config\BankImportConfig::setScoringWeights
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Config\BankImportConfig;

/**
 * BankImportConfig - Scoring Weights
 *
 * Tests the scoring weight configuration system integrated with BankImportConfig.
 */
class BankImportConfigScoringWeightsTest extends TestCase
{
    /**
     * Reset configuration to defaults before each test
     */
    protected function setUp(): void
    {
        BankImportConfig::resetToDefaults();
    }

    /**
     * Test 1: Default scoring weights are 1.0 (no adjustment)
     */
    public function testDefaultScoringWeightsAreOne(): void
    {
        $this->assertSame(1.0, BankImportConfig::getScoringRecencyWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringAmountWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringTypeWeight());
    }

    /**
     * Test 2: Set and get recency weight
     */
    public function testSetAndGetRecencyWeight(): void
    {
        BankImportConfig::setScoringRecencyWeight(2.5);
        $this->assertSame(2.5, BankImportConfig::getScoringRecencyWeight());
    }

    /**
     * Test 3: Set and get amount weight
     */
    public function testSetAndGetAmountWeight(): void
    {
        BankImportConfig::setScoringAmountWeight(0.5);
        $this->assertSame(0.5, BankImportConfig::getScoringAmountWeight());
    }

    /**
     * Test 4: Set and get type weight
     */
    public function testSetAndGetTypeWeight(): void
    {
        BankImportConfig::setScoringTypeWeight(1.8);
        $this->assertSame(1.8, BankImportConfig::getScoringTypeWeight());
    }

    /**
     * Test 5: Reject zero weight
     */
    public function testRejectZeroRecencyWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be greater than 0');
        BankImportConfig::setScoringRecencyWeight(0.0);
    }

    /**
     * Test 6: Reject negative weight
     */
    public function testRejectNegativeAmountWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be greater than 0');
        BankImportConfig::setScoringAmountWeight(-1.5);
    }

    /**
     * Test 7: Accept very small positive weight
     */
    public function testAcceptVerySmallWeight(): void
    {
        BankImportConfig::setScoringRecencyWeight(0.001);
        $this->assertSame(0.001, BankImportConfig::getScoringRecencyWeight());
    }

    /**
     * Test 8: Accept large weight
     */
    public function testAcceptLargeWeight(): void
    {
        BankImportConfig::setScoringTypeWeight(999.99);
        $this->assertSame(999.99, BankImportConfig::getScoringTypeWeight());
    }

    /**
     * Test 9: getScoringWeights returns associative array with all weights
     */
    public function testGetScoringWeightsReturnsArray(): void
    {
        BankImportConfig::setScoringRecencyWeight(2.0);
        BankImportConfig::setScoringAmountWeight(0.5);
        BankImportConfig::setScoringTypeWeight(1.5);

        $weights = BankImportConfig::getScoringWeights();

        $this->assertIsArray($weights);
        $this->assertArrayHasKey('recency', $weights);
        $this->assertArrayHasKey('amount', $weights);
        $this->assertArrayHasKey('type', $weights);
        $this->assertSame(2.0, $weights['recency']);
        $this->assertSame(0.5, $weights['amount']);
        $this->assertSame(1.5, $weights['type']);
    }

    /**
     * Test 10: setScoringWeights sets all three weights at once
     */
    public function testSetScoringWeightsBatch(): void
    {
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);

        $this->assertSame(2.0, BankImportConfig::getScoringRecencyWeight());
        $this->assertSame(0.5, BankImportConfig::getScoringAmountWeight());
        $this->assertSame(1.5, BankImportConfig::getScoringTypeWeight());
    }

    /**
     * Test 11: setScoringWeights validates all weights before setting any
     *
     * Ensures transaction-like behavior - all or nothing
     */
    public function testSetScoringWeightsValidatesAllBeforeSetting(): void
    {
        // Set initial values
        BankImportConfig::setScoringWeights(1.0, 1.0, 1.0);

        // Try to set with invalid weight in the batch
        try {
            BankImportConfig::setScoringWeights(2.0, -0.5, 1.5);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        // First weight should still be 1.0 (not partially set)
        $this->assertSame(1.0, BankImportConfig::getScoringRecencyWeight());
    }

    /**
     * Test 12: Scoring weights included in getAllSettings()
     */
    public function testScoringWeightsInGetAllSettings(): void
    {
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);

        $settings = BankImportConfig::getAllSettings();

        $this->assertArrayHasKey('scoring_weights', $settings);
        $this->assertIsArray($settings['scoring_weights']);
        $this->assertSame(2.0, $settings['scoring_weights']['recency']);
        $this->assertSame(0.5, $settings['scoring_weights']['amount']);
        $this->assertSame(1.5, $settings['scoring_weights']['type']);
    }

    /**
     * Test 13: resetToDefaults resets scoring weights to 1.0
     */
    public function testResetToDefaultsResetsScoringWeights(): void
    {
        // Set custom weights
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);

        // Reset to defaults
        BankImportConfig::resetToDefaults();

        // All weights should be back to 1.0
        $this->assertSame(1.0, BankImportConfig::getScoringRecencyWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringAmountWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringTypeWeight());
    }

    /**
     * Test 14: Weights persist across multiple get calls
     */
    public function testWeightsPersistAcrossMultipleGets(): void
    {
        BankImportConfig::setScoringRecencyWeight(3.14159);

        // Get multiple times - should be consistent
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(3.14159, BankImportConfig::getScoringRecencyWeight());
        }
    }

    /**
     * Test 15: Can set weights independently without affecting others
     */
    public function testSetWeightsIndependently(): void
    {
        // Set initial batch
        BankImportConfig::setScoringWeights(1.0, 1.0, 1.0);

        // Modify one weight
        BankImportConfig::setScoringRecencyWeight(2.0);

        // Others should remain unchanged
        $this->assertSame(2.0, BankImportConfig::getScoringRecencyWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringAmountWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringTypeWeight());

        // Modify another
        BankImportConfig::setScoringAmountWeight(0.3);

        // All should be as expected
        $this->assertSame(2.0, BankImportConfig::getScoringRecencyWeight());
        $this->assertSame(0.3, BankImportConfig::getScoringAmountWeight());
        $this->assertSame(1.0, BankImportConfig::getScoringTypeWeight());
    }

    /**
     * Test 16: Float precision is maintained
     */
    public function testFloatPrecisionMaintained(): void
    {
        $precision = 1.23456789;
        BankImportConfig::setScoringRecencyWeight($precision);

        $retrieved = BankImportConfig::getScoringRecencyWeight();

        // PHP float comparison with some tolerance
        $this->assertEqualsWithDelta($precision, $retrieved, 0.00000001);
    }
}
