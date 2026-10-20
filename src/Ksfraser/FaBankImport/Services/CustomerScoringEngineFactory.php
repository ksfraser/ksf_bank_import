<?php

/**
 * Customer Scoring Engine Factory
 *
 * Factory for creating a pre-configured ScoringRuleEngine with customer-specific
 * rules. Reuses the same generic rules as SupplierScoringEngineFactory but with
 * different weights optimized for customer matching (e.g., refunds, returns).
 *
 * Reusable Rules:
 * - BankAccountMatchRule (exact customer account match)
 * - CustomerNameMatchRule (or reuse generic keyword matching)
 * - AmountMatchRule (match refund amounts within tolerance)
 * - RefundDetectionRule (detect customer refund transactions)
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Services\Rules\BankAccountMatchRule;
use Ksfraser\FaBankImport\Services\Rules\VendorNameMatchRule;
use Ksfraser\FaBankImport\Services\Rules\AmountMatchRule;
use Ksfraser\FaBankImport\Services\Rules\RefundDetectionRule;

/**
 * @BABOK CLASS ROLLUP: CustomerScoringEngineFactory — 7 methods; BR-001 (7); BR-002 (7); FR-008 (7)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/CustomerScoringEngineFactory.php; coverage: docs/TestCases/ (file rollup)
 * Factory for Creating Customer-Configured Scoring Engine
 *
 * Demonstrates reusability of generic rules in different contexts.
 * Uses same rules as SupplierScoringEngineFactory but with customer-optimized
 * weights. Can be extended with CustomerNameMatchRule in future.
 *
 * WEIGHTS (Customer Context):
 * - Bank account: 100 (must match - required for customer refunds)
 * - Name/description: 20 (lower weight than supplier - more fuzzy matching)
 * - Amount: 30 (higher weight - refund amounts are usually exact)
 * - Refund detection: 15 (higher boost for refund transactions)
 *
 * @since 7.6
 */
final class CustomerScoringEngineFactory
{
    /**
     * Configuration for customer matching
     *
     * @var CustomerMatchingConfiguration
     */
    private CustomerMatchingConfiguration $configuration;

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct
     * Constructor
     *
     * @param CustomerMatchingConfiguration $configuration Configuration for rules
     * @since 7.6
     */
    public function __construct(CustomerMatchingConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getConfiguration
     * Get configuration reference
     *
     * @return CustomerMatchingConfiguration Current configuration
     * @since 7.6
     */
    public function getConfiguration(): CustomerMatchingConfiguration
    {
        return $this->configuration;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: createBankAccountRule
     * Create bank account matching rule
     *
     * PROD REQUIREMENT: Customer bank account MUST match exactly
     * Reuses generic BankAccountMatchRule from supplier context.
     *
     * @return BankAccountMatchRule Bank account matching rule
     * @since 7.6
     */
    private function createBankAccountRule(): BankAccountMatchRule
    {
        return new BankAccountMatchRule();
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: createCustomerNameRule
     * Create customer name matching rule
     *
     * Reuses generic VendorNameMatchRule - identical logic applies
     * to customer name matching (just different context/weights).
     *
     * @return VendorNameMatchRule Customer name matching rule
     * @since 7.6
     */
    private function createCustomerNameRule(): VendorNameMatchRule
    {
        return new VendorNameMatchRule();
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: createAmountRule
     * Create amount matching rule
     *
     * Higher tolerance for customer refunds (may include processing fees).
     * Reuses generic AmountMatchRule.
     *
     * @return AmountMatchRule Amount matching rule
     * @since 7.6
     */
    private function createAmountRule(): AmountMatchRule
    {
        // Customer refunds: 2% tolerance (slightly higher than supplier 1%)
        return new AmountMatchRule($this->configuration->getAmountTolerance());
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: createRefundRule
     * Create refund detection rule
     *
     * Detects customer refund transactions and provides scoring boost.
     * Uses generic RefundDetectionRule.
     *
     * @return RefundDetectionRule Refund detection rule
     * @since 7.6
     */
    private function createRefundRule(): RefundDetectionRule
    {
        return new RefundDetectionRule();
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: createEngine
     * Create configured scoring engine with all customer matching rules
     *
     * Returns a ScoringRuleEngine instance with all customer-specific rules
     * registered and weighted according to configuration.
     *
     * Demonstrates DRY principle: same rules, different weights, same factory pattern.
     *
     * @return ScoringRuleEngine Fully configured scoring engine
     * @since 7.6
     */
    public function createEngine(): ScoringRuleEngine
    {
        $engine = new ScoringRuleEngine();

        // Register rules with customer-optimized weights
        // (different from supplier weights - demonstrates reusability)
        $engine->register($this->createBankAccountRule(), 1.0);      // Bank account: 100 required
        $engine->register($this->createCustomerNameRule(), 0.2);     // Customer name: 20 (lower than supplier)
        $engine->register($this->createAmountRule(), 0.3);           // Amount: 30 (higher than supplier)
        $engine->register($this->createRefundRule(), 0.15);          // Refund: 15 boost for refund type

        return $engine;
    }
}
