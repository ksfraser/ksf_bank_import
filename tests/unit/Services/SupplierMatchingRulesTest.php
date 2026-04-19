<?php

/**
 * Supplier Matching Rules Test Suite
 *
 * Tests for configurable supplier matching rules that replicate PROD bank account
 * matching logic using the scoring rules engine with configurable weights.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit
 * @subpackage Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\SupplierMatchingConfiguration;
use Ksfraser\FaBankImport\Services\SupplierScoringEngineFactory;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Services\Rules\BankAccountMatchRule;
use Ksfraser\FaBankImport\Services\Rules\VendorNameMatchRule;
use Ksfraser\FaBankImport\Services\Rules\AmountMatchRule;
use Ksfraser\FaBankImport\Services\Rules\InvoiceDetectionRule;
use Ksfraser\FaBankImport\Services\VendorCandidate;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

class SupplierMatchingRulesTest extends TestCase
{
    private SupplierMatchingConfiguration $config;
    private SupplierScoringEngineFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new SupplierMatchingConfiguration();
        $this->factory = new SupplierScoringEngineFactory($this->config);
    }

    public function testConfigurationHasDefaultWeights(): void
    {
        $weights = $this->config->getWeights();
        $this->assertIsArray($weights);
        $this->assertArrayHasKey('bank_account', $weights);
        $this->assertArrayHasKey('vendor_name', $weights);
        $this->assertArrayHasKey('amount_match', $weights);
        $this->assertArrayHasKey('invoice_detection', $weights);
    }

    public function testBankAccountWeightDefaultsToRequired(): void
    {
        $weights = $this->config->getWeights();
        $this->assertEquals(100, $weights['bank_account']);
    }

    public function testConfigurationAllowsWeightOverride(): void
    {
        $customWeights = ['bank_account' => 100, 'vendor_name' => 20, 'amount_match' => 15, 'invoice_detection' => 10];
        $this->config->setWeights($customWeights);
        $this->assertEquals($customWeights, $this->config->getWeights());
    }

    public function testFactoryHasConfigurationReference(): void
    {
        $this->assertSame($this->config, $this->factory->getConfiguration());
    }

    public function testFactoryCreatesScoringEngine(): void
    {
        $engine = $this->factory->createEngine();
        $this->assertInstanceOf(ScoringRuleEngine::class, $engine);
    }

    public function testBankAccountRuleExactMatch(): void
    {
        $rule = new BankAccountMatchRule();
        $transaction = ['account' => '123456789', 'partner_account' => '123456789'];
        $match = $this->createMockSupplier(1, 'Test');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(100.0, $score);
    }

    public function testBankAccountRuleNoMatch(): void
    {
        $rule = new BankAccountMatchRule();
        $transaction = ['account' => '123456789', 'partner_account' => '987654321'];
        $match = $this->createMockSupplier(1, 'Test');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(-50.0, $score);
    }

    public function testVendorNameRulePartialMatch(): void
    {
        $rule = new VendorNameMatchRule();
        $transaction = ['memo' => 'ACME CORPORATION PAYMENT'];
        $match = $this->createMockSupplier(1, 'ACME CORP');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertGreaterThan(0, $score);
    }

    public function testAmountMatchRuleExactAmount(): void
    {
        $rule = new AmountMatchRule();
        $transaction = ['amount' => 1000.00, 'partner_amount' => 1000.00];
        $match = $this->createMockSupplier(1, 'Test');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(20.0, $score);
    }

    public function testAmountMatchRuleWithinTolerance(): void
    {
        $rule = new AmountMatchRule(1.0);
        $transaction = ['amount' => 1005.00, 'partner_amount' => 1000.00];
        $match = $this->createMockSupplier(1, 'Test');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertGreaterThan(10.0, $score);
    }

    public function testInvoiceDetectionRuleIsInvoice(): void
    {
        $rule = new InvoiceDetectionRule();
        $transaction = ['is_invoice' => true];
        $match = $this->createMockSupplier(1, 'Test');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(10.0, $score);
    }

    public function testInvoiceDetectionRuleNotInvoice(): void
    {
        $rule = new InvoiceDetectionRule();
        $transaction = ['is_invoice' => false, 'type' => 5];
        $match = $this->createMockSupplier(1, 'Test');
        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(0, $score);
    }

    public function testConfigurationHasConfidenceThreshold(): void
    {
        $threshold = $this->config->getMinimumConfidenceThreshold();
        $this->assertIsInt($threshold);
        $this->assertGreaterThan(0, $threshold);
    }

    public function testDefaultConfidenceThresholdMatchesProd(): void
    {
        $threshold = $this->config->getMinimumConfidenceThreshold();
        $this->assertEquals(50, $threshold);
    }

    public function testConfigurationMaxMatchesDefault(): void
    {
        $maxMatches = $this->config->getMaximumAutoMatches();
        $this->assertIsInt($maxMatches);
        $this->assertGreaterThan(0, $maxMatches);
    }

    public function testConfigurationCanSetProdDefaults(): void
    {
        $this->config->setProdDefaults();
        $this->assertEquals(50, $this->config->getMinimumConfidenceThreshold());
        $this->assertEquals(2, $this->config->getMaximumAutoMatches());
        $this->assertEquals(100, $this->config->getWeight('bank_account'));
    }

    public function testAllRuleTypesAreCreatedByFactory(): void
    {
        $engine = $this->factory->createEngine();
        $this->assertInstanceOf(ScoringRuleEngine::class, $engine);
    }

    /**
     * Create VendorCandidate for testing
     */
    private function createMockSupplier(int $id = 1, string $name = 'Test'): VendorCandidate
    {
        return new VendorCandidate($id, $name, 'test_account');
    }
}
