<?php

/**
 * Supplier Matching Rules Factory
 *
 * Creates and configures supplier-specific matching rules for use with the
 * scoring engine. Encapsulates the logic for bank account, vendor name, amount,
 * and invoice detection matching criteria.
 *
 * FLOW:
 * ```
 * SupplierMatchingRules
 *     ├─ Configuration Reference
 *     ├─ createBankAccountRule() → BankAccountMatchRule
 *     ├─ createVendorNameRule() → VendorNameMatchRule
 *     ├─ createAmountMatchRule() → AmountMatchRule
 *     ├─ createInvoiceDetectionRule() → InvoiceDetectionRule
 *     └─ createScoringEngine() → ScoringRuleEngine (with all rules)
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
use Ksfraser\FaBankImport\Services\Scoring\ScoringRule;

/**
 * Factory for Creating Supplier-Specific Matching Rules
 *
 * Replicates PROD supplier matching behavior (bank account lookup) using the
 * configurable scoring engine. Creates individual rules that can be registered
 * with the engine.
 *
 * @since 7.6
 */
final class SupplierMatchingRules
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
     * Score: 100 (match) or 0 (no match)
     *
     * @return ScoringRule Bank account matching rule
     * @since 7.6
     */
    public function createBankAccountRule(): ScoringRule
    {
        return new class($this->configuration->getWeight('bank_account')) implements ScoringRule {
            /**
             * @var int
             */
            private $weight;

            /**
             * @param int $weight
             */
            public function __construct(int $weight = 100)
            {
                $this->weight = $weight;
            }

            /**
             * @inheritDoc
             */
            public function getName(): string
            {
                return 'bank_account';
            }

            /**
             * @inheritDoc
             */
            public function getWeight(): int
            {
                return $this->weight;
            }

            /**
             * @inheritDoc
             */
            public function calculateScore(array $context): int
            {
                $bank_account = $context['bank_account'] ?? '';
                $vendor_bank = $context['vendor_bank_account'] ?? '';

                if (empty($bank_account) || empty($vendor_bank)) {
                    return 0;
                }

                // Exact match required (case-insensitive, trimmed)
                return strcasecmp(trim($bank_account), trim($vendor_bank)) === 0 ? 100 : 0;
            }
        };
    }

    /**
     * Create vendor name matching rule
     *
     * Uses keyword scoring for vendor name matching. Higher score for exact
     * or near-exact matches, lower scores for partial matches.
     *
     * @return ScoringRule Vendor name matching rule
     * @since 7.6
     */
    public function createVendorNameRule(): ScoringRule
    {
        return new class($this->configuration->getWeight('vendor_name')) implements ScoringRule {
            /**
             * @var int
             */
            private $weight;

            /**
             * @param int $weight
             */
            public function __construct(int $weight = 30)
            {
                $this->weight = $weight;
            }

            /**
             * @inheritDoc
             */
            public function getName(): string
            {
                return 'vendor_name';
            }

            /**
             * @inheritDoc
             */
            public function getWeight(): int
            {
                return $this->weight;
            }

            /**
             * @inheritDoc
             */
            public function calculateScore(array $context): int
            {
                $description = strtoupper($context['description'] ?? '');
                $vendor_name = strtoupper($context['vendor_name'] ?? '');

                if (empty($description) || empty($vendor_name)) {
                    return 0;
                }

                // Exact match
                if (strpos($description, $vendor_name) !== false) {
                    return 100;
                }

                // Partial match scoring
                $words = explode(' ', $vendor_name);
                $matches = 0;

                foreach ($words as $word) {
                    if (!empty($word) && strpos($description, $word) !== false) {
                        $matches++;
                    }
                }

                if ($matches === 0) {
                    return 0;
                }

                // Score based on word match percentage
                return (int)round(($matches / count($words)) * 100);
            }
        };
    }

    /**
     * Create amount matching rule
     *
     * Matches amounts within configured tolerance. Exact match scores 100,
     * within tolerance scores proportionally less.
     *
     * @return ScoringRule Amount matching rule
     * @since 7.6
     */
    public function createAmountMatchRule(): ScoringRule
    {
        return new class($this->configuration->getWeight('amount_match'), $this->configuration->getAmountTolerance()) implements ScoringRule {
            /**
             * @var int
             */
            private $weight;

            /**
             * @var float
             */
            private $tolerance;

            /**
             * @param int $weight
             * @param float $tolerance
             */
            public function __construct(int $weight = 20, float $tolerance = 1.0)
            {
                $this->weight = $weight;
                $this->tolerance = $tolerance;
            }

            /**
             * @inheritDoc
             */
            public function getName(): string
            {
                return 'amount_match';
            }

            /**
             * @inheritDoc
             */
            public function getWeight(): int
            {
                return $this->weight;
            }

            /**
             * @inheritDoc
             */
            public function calculateScore(array $context): int
            {
                $amount = (float)($context['amount'] ?? 0);
                $vendor_amount = (float)($context['vendor_amount'] ?? 0);

                if ($amount == 0 || $vendor_amount == 0) {
                    return 0;
                }

                // Exact match
                if ($amount == $vendor_amount) {
                    return 100;
                }

                // Calculate percentage difference
                $diff = abs($amount - $vendor_amount);
                $percent_diff = ($diff / $vendor_amount) * 100;

                // Check if within tolerance
                if ($percent_diff <= $this->tolerance) {
                    return (int)round(100 - ($percent_diff / $this->tolerance) * 10);
                }

                return 0;
            }
        };
    }

    /**
     * Create invoice detection rule
     *
     * Detects supplier invoice transactions and provides extra boost for matching.
     * PROD: Invoice transactions are more likely to be exact matches.
     *
     * @return ScoringRule Invoice detection rule
     * @since 7.6
     */
    public function createInvoiceDetectionRule(): ScoringRule
    {
        return new class($this->configuration->getWeight('invoice_detection')) implements ScoringRule {
            /**
             * @var int
             */
            private $weight;

            /**
             * @param int $weight
             */
            public function __construct(int $weight = 10)
            {
                $this->weight = $weight;
            }

            /**
             * @inheritDoc
             */
            public function getName(): string
            {
                return 'invoice_detection';
            }

            /**
             * @inheritDoc
             */
            public function getWeight(): int
            {
                return $this->weight;
            }

            /**
             * @inheritDoc
             */
            public function calculateScore(array $context): int
            {
                $is_invoice = $context['is_invoice'] ?? false;
                $transaction_type = $context['transaction_type'] ?? 0;

                // ST_SUPPINVOICE = 20
                if ($is_invoice || $transaction_type === 20) {
                    return 100;
                }

                return 0;
            }
        };
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
    public function createScoringEngine(): ScoringRuleEngine
    {
        $engine = new ScoringRuleEngine();

        // Register all rules in priority order
        $engine->registerRule($this->createBankAccountRule());
        $engine->registerRule($this->createVendorNameRule());
        $engine->registerRule($this->createAmountMatchRule());
        $engine->registerRule($this->createInvoiceDetectionRule());

        return $engine;
    }
}
