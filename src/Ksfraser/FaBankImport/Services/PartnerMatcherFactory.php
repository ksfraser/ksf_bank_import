<?php

/**
 * Partner Matcher Factory
 *
 * Creates pre-configured TransactionPartnerMatcher instances for different
 * matching contexts (unified, supplier-only, customer-only, etc.)
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

/**
 * Partner Matcher Factory
 *
 * Factory for creating specialized matchers with different configurations:
 * - Unified matcher (scores all types together with unified weights)
 * - Supplier-only matcher
 * - Customer-only matcher
 * - Bank transfer-only matcher
 *
 * @since 7.6
 */
final class PartnerMatcherFactory
{
    /**
     * Create unified transaction partner matcher
     *
     * Uses supplier configuration weights as baseline for all partner types.
     * Best for initial broad matching.
     *
     * @return TransactionPartnerMatcher Unified matcher instance
     */
    public static function createUnifiedMatcher(): TransactionPartnerMatcher
    {
        $config = new SupplierMatchingConfiguration();
        $factory = new SupplierScoringEngineFactory($config);
        $engine = $factory->createEngine();

        return new TransactionPartnerMatcher($engine, $config);
    }

    /**
     * Create supplier-specific matcher
     *
     * Uses SupplierMatchingConfiguration optimized for supplier transactions.
     *
     * @return TransactionPartnerMatcher Supplier matcher instance
     */
    public static function createSupplierMatcher(): TransactionPartnerMatcher
    {
        $config = new SupplierMatchingConfiguration();
        $factory = new SupplierScoringEngineFactory($config);
        $engine = $factory->createEngine();

        return new TransactionPartnerMatcher($engine, $config);
    }

    /**
     * Create customer-specific matcher
     *
     * Uses CustomerMatchingConfiguration optimized for customer transactions
     * with refund detection and different weighting.
     *
     * @return TransactionPartnerMatcher Customer matcher instance
     */
    public static function createCustomerMatcher(): TransactionPartnerMatcher
    {
        $config = new CustomerMatchingConfiguration();
        $factory = new CustomerScoringEngineFactory($config);
        $engine = $factory->createEngine();

        return new TransactionPartnerMatcher($engine, $config);
    }

    /**
     * Create with custom configuration and engine
     *
     * For advanced use cases where custom configuration or engine is needed.
     *
     * @param ScoringRuleEngine             $engine      Custom scoring engine
     * @param SupplierMatchingConfiguration $config      Custom configuration
     * @return TransactionPartnerMatcher Matcher with custom setup
     */
    public static function createCustom(
        ScoringRuleEngine $engine,
        SupplierMatchingConfiguration $config
    ): TransactionPartnerMatcher {
        return new TransactionPartnerMatcher($engine, $config);
    }
}
