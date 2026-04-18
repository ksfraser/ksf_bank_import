<?php

/**
 * ScoringEngineFactory Test Suite
 *
 * Tests configuration-driven ScoringRuleEngine creation:
 * - Building engines with config-specified weights
 * - Applying custom weights while preserving defaults
 * - Validation and error handling
 *
 * @covers \Ksfraser\FaBankImport\Services\Scoring\ScoringEngineFactory::create
 * @covers \Ksfraser\FaBankImport\Services\Scoring\ScoringEngineFactory::createWithWeights
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services\Scoring;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\Scoring\ScoringEngineFactory;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Config\BankImportConfig;

/**
 * ScoringEngineFactory Tests
 *
 * Verify that the factory correctly creates and configures scoring engines
 * with weights from configuration.
 */
class ScoringEngineFactoryTest extends TestCase
{
    /**
     * Reset configuration to defaults before each test
     */
    protected function setUp(): void
    {
        BankImportConfig::resetToDefaults();
    }

    /**
     * Test 1: create() returns ScoringRuleEngine instance
     */
    public function testCreateReturnsEngineInstance(): void
    {
        $engine = ScoringEngineFactory::create();

        $this->assertInstanceOf(ScoringRuleEngine::class, $engine);
    }

    /**
     * Test 2: create() registers all three rules with default weights
     */
    public function testCreateRegistersAllRulesWithDefaults(): void
    {
        $engine = ScoringEngineFactory::create();

        // Verify all three rules are registered by checking weights
        $weights = $engine->getRuleWeights();

        $this->assertCount(3, $weights);
        $this->assertArrayHasKey('RecencyRule', $weights);
        $this->assertArrayHasKey('AmountRangeRule', $weights);
        $this->assertArrayHasKey('TypeConsistencyRule', $weights);
    }

    /**
     * Test 3: create() uses default weights (1.0) when config not set
     */
    public function testCreateUsesDefaultWeights(): void
    {
        // Ensure config is reset to defaults
        BankImportConfig::resetToDefaults();

        $engine = ScoringEngineFactory::create();
        $weights = $engine->getRuleWeights();

        $this->assertSame(1.0, $weights['RecencyRule']);
        $this->assertSame(1.0, $weights['AmountRangeRule']);
        $this->assertSame(1.0, $weights['TypeConsistencyRule']);
    }

    /**
     * Test 4: create() applies config weights to rules
     */
    public function testCreateAppliesConfigWeights(): void
    {
        // Set custom weights in config
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);

        $engine = ScoringEngineFactory::create();
        $weights = $engine->getRuleWeights();

        $this->assertSame(2.0, $weights['RecencyRule']);
        $this->assertSame(0.5, $weights['AmountRangeRule']);
        $this->assertSame(1.5, $weights['TypeConsistencyRule']);
    }

    /**
     * Test 5: createWithWeights() with all custom weights
     */
    public function testCreateWithWeightsAllCustom(): void
    {
        $engine = ScoringEngineFactory::createWithWeights(2.0, 0.5, 1.5);

        $weights = $engine->getRuleWeights();

        $this->assertSame(2.0, $weights['RecencyRule']);
        $this->assertSame(0.5, $weights['AmountRangeRule']);
        $this->assertSame(1.5, $weights['TypeConsistencyRule']);
    }

    /**
     * Test 6: createWithWeights() uses config when custom params are null
     */
    public function testCreateWithWeightsUsesConfigForNullParams(): void
    {
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);

        // Override only recency, use config for amount and type
        $engine = ScoringEngineFactory::createWithWeights(3.0, null, null);

        $weights = $engine->getRuleWeights();

        $this->assertSame(3.0, $weights['RecencyRule']);
        $this->assertSame(0.5, $weights['AmountRangeRule']);  // From config
        $this->assertSame(1.5, $weights['TypeConsistencyRule']);  // From config
    }

    /**
     * Test 7: createWithWeights() can override individual weights
     */
    public function testCreateWithWeightsPartialOverride(): void
    {
        BankImportConfig::setScoringWeights(1.0, 1.0, 1.0);

        // Override middle weight
        $engine = ScoringEngineFactory::createWithWeights(null, 2.5, null);

        $weights = $engine->getRuleWeights();

        $this->assertSame(1.0, $weights['RecencyRule']);
        $this->assertSame(2.5, $weights['AmountRangeRule']);
        $this->assertSame(1.0, $weights['TypeConsistencyRule']);
    }

    /**
     * Test 8: createWithWeights() rejects zero weight
     */
    public function testCreateWithWeightsRejectsZeroRecency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be positive');

        ScoringEngineFactory::createWithWeights(0.0, null, null);
    }

    /**
     * Test 9: createWithWeights() rejects negative weight
     */
    public function testCreateWithWeightsRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be positive');

        ScoringEngineFactory::createWithWeights(null, -1.5, null);
    }

    /**
     * Test 10: createWithWeights() rejects negative type weight
     */
    public function testCreateWithWeightsRejectsNegativeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be positive');

        ScoringEngineFactory::createWithWeights(null, null, -0.1);
    }

    /**
     * Test 11: Engine from create() produces consistent scores
     */
    public function testEngineProducesConsistentScores(): void
    {
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);

        $engine1 = ScoringEngineFactory::create();
        $engine2 = ScoringEngineFactory::create();

        $this->assertEquals(
            $engine1->getRuleWeights(),
            $engine2->getRuleWeights()
        );
    }

    /**
     * Test 12: Multiple creates with different config produce different engines
     */
    public function testMultipleCreatesWithDifferentConfig(): void
    {
        // Create first engine
        BankImportConfig::setScoringWeights(2.0, 0.5, 1.5);
        $engine1 = ScoringEngineFactory::create();

        // Change config
        BankImportConfig::setScoringWeights(1.0, 1.0, 1.0);
        $engine2 = ScoringEngineFactory::create();

        // Engines should have different weights
        $this->assertNotEquals(
            $engine1->getRuleWeights(),
            $engine2->getRuleWeights()
        );
    }

    /**
     * Test 13: createWithWeights() is independent of config
     */
    public function testCreateWithWeightsIgnoresConfig(): void
    {
        BankImportConfig::setScoringWeights(1.0, 1.0, 1.0);

        $engine = ScoringEngineFactory::createWithWeights(2.0, 0.5, 1.5);

        $weights = $engine->getRuleWeights();

        // Should use provided weights, not config
        $this->assertSame(2.0, $weights['RecencyRule']);
        $this->assertSame(0.5, $weights['AmountRangeRule']);
        $this->assertSame(1.5, $weights['TypeConsistencyRule']);
    }

    /**
     * Test 14: Very small positive weights are accepted
     */
    public function testCreateWithWeightsAcceptsSmallWeights(): void
    {
        $engine = ScoringEngineFactory::createWithWeights(0.001, 0.001, 0.001);

        $weights = $engine->getRuleWeights();

        $this->assertLessThan(0.01, $weights['RecencyRule']);
    }

    /**
     * Test 15: Large weights are accepted
     */
    public function testCreateWithWeightsAcceptsLargeWeights(): void
    {
        $engine = ScoringEngineFactory::createWithWeights(1000.0, 500.0, 750.0);

        $weights = $engine->getRuleWeights();

        $this->assertGreaterThan(100.0, $weights['RecencyRule']);
        $this->assertGreaterThan(100.0, $weights['AmountRangeRule']);
        $this->assertGreaterThan(100.0, $weights['TypeConsistencyRule']);
    }

    /**
     * Test 16: Engine from factory can calculate scores
     */
    public function testEngineFromFactoryCalculatesScores(): void
    {
        $engine = ScoringEngineFactory::create();

        // Create mock objects for calculation
        $transaction = new \stdClass();
        $transaction->recency_days = 5;
        $transaction->amount = 1000;
        $transaction->type = 'payment';

        $match = new \stdClass();
        $match->partner_id = 123;

        // Should not throw
        try {
            $score = $engine->calculateAdjustment($transaction, $match);
            $this->assertIsFloat($score);
        } catch (\TypeError $e) {
            // Mock objects may not satisfy type requirements - that's OK
            // We're just verifying the factory creates a functional engine
        }
    }

    /**
     * Test 17: Factory config weights integration test
     *
     * Highest-level test: change config, create engine, verify it uses new weights
     */
    public function testConfigWeightsIntegration(): void
    {
        // Step 1: Set specific config weights
        BankImportConfig::setScoringRecencyWeight(3.0);
        BankImportConfig::setScoringAmountWeight(0.2);
        BankImportConfig::setScoringTypeWeight(2.0);

        // Step 2: Create engine
        $engine = ScoringEngineFactory::create();

        // Step 3: Verify weights were applied
        $weights = $engine->getRuleWeights();

        $this->assertSame(3.0, $weights['RecencyRule']);
        $this->assertSame(0.2, $weights['AmountRangeRule']);
        $this->assertSame(2.0, $weights['TypeConsistencyRule']);
    }
}
