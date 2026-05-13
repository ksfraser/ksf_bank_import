<?php

/**
 * Supplier Scoring Engine Factory
 *
 * Factory for creating a pre-configured ScoringRuleEngine with supplier-specific
 * rules. Handles the orchestration of rule creation and registration with the engine.
 *
 * FLOW:
 * ```
 * SupplierScoringEngineFactory
 *     ├─ Configuration Reference
 *     ├─ createBankAccountRule()
 *     ├─ createVendorNameRule()
 *     ├─ createAmountRule()
 *     ├─ createInvoiceRule()
 *     └─ createEngine() → Returns ScoringRuleEngine with rules registered
 * ```
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Services\Rules\BankAccountMatchRule;
use Ksfraser\FaBankImport\Services\Rules\VendorNameMatchRule;
use Ksfraser\FaBankImport\Services\Rules\AmountMatchRule;
use Ksfraser\FaBankImport\Services\Rules\InvoiceDetectionRule;

/**
 * Factory for Creating Supplier-Configured Scoring Engine
 *
 * Single responsibility: Create and register supplier-specific rules
 * with a ScoringRuleEngine. Configuration is injected for DI.
 *
 * @since 7.6
 */
final class SupplierScoringEngineFactory
{
    /**
     * Configuration for supplier matching
     *
     * @var SupplierMatchingConfiguration
     * @since 7.6
     */
    private SupplierMatchingConfiguration $configuration;

    /**
     * Constructor
     *
     * @param SupplierMatchingConfiguration $configuration Configuration for rules
     * @since 7.6
     */
    public function __construct(SupplierMatchingConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get configuration reference
     *
     * @return SupplierMatchingConfiguration Current configuration
     * @since 7.6
     */
    public function getConfiguration(): SupplierMatchingConfiguration
    {
        return $this->configuration;
    }

    /**
     * Create bank account matching rule
     *
     * PROD REQUIREMENT: Bank account MUST match exactly
     *
     * @return BankAccountMatchRule Bank account matching rule
     * @since 7.6
     */
    private function createBankAccountRule(): BankAccountMatchRule
    {
        return new BankAccountMatchRule();
    }

    /**
     * Create vendor name matching rule
     *
     * Uses keyword scoring for vendor name matching.
     *
     * @return VendorNameMatchRule Vendor name matching rule
     * @since 7.6
     */
    private function createVendorNameRule(): VendorNameMatchRule
    {
        return new VendorNameMatchRule();
    }

    /**
     * Create amount matching rule
     *
     * Matches amounts within configured tolerance.
     *
     * @return AmountMatchRule Amount matching rule
     * @since 7.6
     */
    private function createAmountRule(): AmountMatchRule
    {
        return new AmountMatchRule($this->configuration->getAmountTolerance());
    }

    /**
     * Create invoice detection rule
     *
     * Detects supplier invoice transactions.
     *
     * @return InvoiceDetectionRule Invoice detection rule
     * @since 7.6
     */
    private function createInvoiceRule(): InvoiceDetectionRule
    {
        return new InvoiceDetectionRule();
    }

    /**
     * Create configured scoring engine with all supplier matching rules
     *
     * Returns a ScoringRuleEngine instance with all supplier-specific rules
     * registered and weighted according to configuration.
     *
     * @return ScoringRuleEngine Fully configured scoring engine
     * @since 7.6
     */
    public function createEngine(): ScoringRuleEngine
    {
        $engine = new ScoringRuleEngine();

        // Register all rules with configured weights (weights are normalized to 0-1)
        // ScoringRuleEngine uses multiplier weights internally
        $engine->register($this->createBankAccountRule(), 1.0);      // Bank account weight: 100, highest priority
        $engine->register($this->createVendorNameRule(), 0.3);       // Vendor name weight: 30
        $engine->register($this->createAmountRule(), 0.2);           // Amount weight: 20
        $engine->register($this->createInvoiceRule(), 0.1);          // Invoice weight: 10

        return $engine;
    }
}
