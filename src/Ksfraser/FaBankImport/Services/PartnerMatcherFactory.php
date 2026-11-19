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

/**

 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Matching\GenericMatcherAdapter;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;

/**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006

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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Create unified matcher backed by the shared GenericMatcher engine.
     *
     * Scores candidates through ksf_FA_Common's GenericMatcher (via
     * GenericMatcherAdapter) instead of the local ScoringRuleEngine. Name
     * similarity dominates (70%); amount, date and reference contribute when
     * the candidate row supplies them.
     *
     * @return TransactionPartnerMatcher Shared-engine unified matcher instance
     * @since 7.6
     */
    public static function createSharedEngineMatcher(): TransactionPartnerMatcher
    {
        $config = new SupplierMatchingConfiguration();
        $factory = new SupplierScoringEngineFactory($config);
        $engine = $factory->createEngine();

        return new TransactionPartnerMatcher(
            $engine,
            $config,
            new GenericMatcherAdapter()
        );
    }
}
